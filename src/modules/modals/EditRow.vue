<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog v-if="showModal"
		data-cy="editRowModal"
		:name="t('tables', 'Edit row')"
		size="large"
		@closing="actionCancel">
		<div class="modal__content" @keydown="onKeydown">
			<div v-for="column in nonMetaColumns" :key="column.id">
				<ColumnFormComponent
					:column="column"
					:value.sync="localRow[column.id]" />
				<NcNoteCard v-if="column.mandatory && !isValueValidForColumn(localRow[column.id], column)"
					type="error">
					{{ t('tables', '"{columnTitle}" should not be empty', { columnTitle: column.title }) }}
				</NcNoteCard>
				<NcNoteCard v-if="localRow[column.id] && column.type === 'text-link' && !isValidUrlProtocol(localRow[column.id])"
					type="error">
					{{ t('tables', 'Invalid protocol. Allowed: {allowed}', {allowed: allowedProtocols.join(', ')}) }}
				</NcNoteCard>
			</div>
			<div class="row">
				<div class="fix-col-4 space-T" :class="{'justify-between': showDeleteButton, 'end': !showDeleteButton}">
					<div v-if="showDeleteButton">
						<NcButton v-if="!prepareDeleteRow" :aria-label="t('tables', 'Delete')" type="error" data-cy="editRowDeleteButton" @click="prepareDeleteRow = true">
							{{ t('tables', 'Delete') }}
						</NcButton>
						<NcButton v-if="prepareDeleteRow"
							data-cy="editRowDeleteConfirmButton"
							:wide="true"
							:aria-label="t('tables', 'I really want to delete this row!')"
							type="error"
							@click="actionDeleteRow">
							{{ t('tables', 'I really want to delete this row!') }}
						</NcButton>
					</div>
					<NcButton v-if="canUpdateData(element) && !localLoading" :aria-label="t('tables', 'Save')" type="primary"
						data-cy="editRowSaveButton"
						:disabled="hasEmptyMandatoryRows || hasInvalidUrlProtocol"
						@click="actionConfirm">
						{{ t('tables', 'Save') }}
					</NcButton>
					<div v-if="localLoading" class="icon-loading" style="margin-left: 20px;" />
				</div>
			</div>
		</div>
	</NcDialog>
</template>

<script>
import { NcDialog, NcButton, NcNoteCard } from '@nextcloud/vue'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import '@nextcloud/dialogs/style.css'
import ColumnFormComponent from '../main/partials/ColumnFormComponent.vue'
import permissionsMixin from '../../shared/components/ncTable/mixins/permissionsMixin.js'
import rowHelper from '../../shared/components/ncTable/mixins/rowHelper.js'
import { mapActions } from 'pinia'
import { useTablesStore } from '../../store/store.js'
import { useDataStore } from '../../store/data.js'
import { ALLOWED_PROTOCOLS } from '../../shared/constants.ts'

export default {
	name: 'EditRow',
	components: {
		NcDialog,
		NcButton,
		ColumnFormComponent,
		NcNoteCard,
	},
	mixins: [permissionsMixin, rowHelper],
	props: {
		showModal: {
			type: Boolean,
			default: false,
		},
		columns: {
			type: Array,
			default: null,
		},
		row: {
			type: Object,
			default: null,
		},
		isView: {
			type: Boolean,
			default: false,
		},
		element: {
			type: Object,
			default: null,
		},
	},
	data() {
		return {
			localRow: null,
			prepareDeleteRow: false,
			localLoading: false,
			allowedProtocols: ALLOWED_PROTOCOLS,
		}
	},
	computed: {
		showDeleteButton() {
			return this.canDeleteData(this.element) && !this.localLoading
		},
		nonMetaColumns() {
			return this.columns.filter(col => col.id >= 0)
		},
		hasEmptyMandatoryRows() {
			return this.checkMandatoryFields(this.localRow)
		},
		hasInvalidUrlProtocol() {
			return this.nonMetaColumns.some(col => col.type === 'text-link' && !this.isValidUrlProtocol(this.localRow[col.id]))
		},
	},
	watch: {
		row() {
			if (this.row) {
				if (this.$router.currentRoute.path.includes('/row/')) {
					this.$router.replace(this.$router.currentRoute.path.split('/row/')[0])
				}
				this.$router.push(this.$router.currentRoute.path + '/row/' + this.row.id)
				this.setActiveRowId(null)
				this.loadValues()
			}
		},
	},
	mounted() {
		this.loadValues()
	},
	methods: {
		...mapActions(useDataStore, ['updateRow', 'removeRow']),
		...mapActions(useTablesStore, ['setActiveRowId']),
		t,
		loadValues() {
			if (this.row) {
				const tmp = {}
				this.row.data.forEach(item => {
					tmp[item.columnId] = item.value
				})
				this.localRow = Object.assign({}, tmp)
			}
		},
		actionCancel() {
			// Remove the row path from URL if it exists
			if (this.$router && this.$router.currentRoute.path.includes('/row/')) {
				this.$router.back()
			}
			this.reset()
			this.$emit('close')
		},
		async actionConfirm() {
			this.localLoading = true
			await this.sendRowToBE()
			this.localLoading = false
			this.actionCancel()
		},
		async sendRowToBE() {
			await this.loadStore()

			const data = []
			for (const [key, value] of Object.entries(this.localRow)) {
				data.push({
					columnId: key,
					value: value ?? '',
				})
			}

			const res = await this.updateRow({
				id: this.row.id,
				isView: this.isView,
				elementId: this.element.id,
				data,
			})
			if (!res) {
				showError(t('tables', 'Could not update row'))
			}
		},
		reset() {
			this.localRow = {}
			this.dataLoaded = false
			this.prepareDeleteRow = false
		},
		actionDeleteRow() {
			this.deleteRowAtBE(this.row.id)
		},
		async deleteRowAtBE(rowId) {
			await this.loadStore()

			this.localLoading = true
			const res = await this.removeRow({
				rowId,
				isView: this.isView,
				elementId: this.element.id,
			})
			if (!res) {
				showError(t('tables', 'Could not delete row.'))
			}
			this.localLoading = false
			this.actionCancel()
		},
		async loadStore() {
			if (this.tablesStore) { return }

			const { default: store } = await import('../../store/store.js')
			this.tablesStore = store
		},
		onKeydown(event) {
			if (event.key === 'Enter' && event.ctrlKey) {
				this.actionConfirm()
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.modal-mask {
	z-index: 9999;
}

.modal__content {
	padding: 20px;

	:where(.row .space-T, .row.space-T) {
		padding-top: 20px;
	}

	:where([class*='fix-col-']) {
		display: flex;
	}

	:where(.slot) {
		align-items: baseline;
	}

	:where(.end) {
		justify-content: end;
	}

	:where(.slot.fix-col-2) {
		min-width: 50%;
	}

	:where(.fix-col-1.end) {
		display: flex;
		justify-content: flex-end;
	}

	:where(.fix-col-3) {
		display: inline-block;
	}

	:where(.slot.fix-col-4 input, .slot.fix-col-4 .row) {
		min-width: 100% !important;
	}

	:where(.name-parts) {
		display: block !important;
		max-width: fit-content !important;
	}
}
</style>
