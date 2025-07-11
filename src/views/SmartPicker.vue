<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="picker-content">
		<h2 class="picker-title">
			{{ t('tables', 'Nextcloud Tables') }}
		</h2>
		<div class="selection-wrapper">
			<div class="selection">
				<div class="space-T space-B">
					<Search :value.sync="value" />
				</div>

				<div class="space-T space-B">
					<h3>{{ t('tables', 'Render mode') }}</h3>
					<div class="radio">
						<NcCheckboxRadioSwitch
							:checked.sync="renderMode"
							value="link"
							name="render-mode"
							type="radio">
							<IconLink :size="20" />{{ t('tables', 'Link') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch
							:checked.sync="renderMode"
							value="content"
							name="render-mode"
							type="radio">
							<IconText :size="20" />{{ t('tables', 'Content') }}
						</NcCheckboxRadioSwitch>
					</div>
				</div>

				<div v-if="getLink" class="space-T space-B">
					<h3>{{ t('tables', 'Preview') }}</h3>
					<div class="preview space-T">
						<div v-if="previewLoading">
							<NcLoadingIcon />
						</div>
						<div v-else>
							<LinkReferenceWidget v-if="renderMode === 'link'" :rich-object="richObject" />
							<ContentReferenceWidget v-if="renderMode === 'content'" :rich-object="richObject" />
						</div>
					</div>
				</div>
				<NcEmptyContent v-else>
					<template #icon>
						<IconTables />
					</template>
				</NcEmptyContent>
			</div>
		</div>

		<div class="select-button">
			<NcButton type="primary" :disabled="value === null" :aria-label="t('tables', 'Select')" @click="selectReference">
				<template #icon>
					<IconCheck :size="20" />
				</template>
				{{ t('tables', 'Insert') }}
			</NcButton>
		</div>
	</div>
</template>
<script>
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { NcButton, NcCheckboxRadioSwitch, NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import IconTables from '../shared/assets/icons/IconTables.vue'
import Search from './sections/Search.vue'
import IconLink from 'vue-material-design-icons/Link.vue'
import IconText from 'vue-material-design-icons/Text.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import displayError from '../shared/utils/displayError.js'
import { useTablesStore } from '../store/store.js'

export default {

	components: {
		IconTables,
		IconLink,
		IconText,
		IconCheck,
		NcEmptyContent,
		Search,
		NcCheckboxRadioSwitch,
		NcButton,
		LinkReferenceWidget: import('./LinkReferenceWidget.vue'),
		ContentReferenceWidget: import('./ContentReferenceWidget.vue'),
		NcLoadingIcon,
	},

	setup() {
		const tablesStore = useTablesStore()
		return { tablesStore }
	},

	data() {
		return {
			renderMode: 'link', // { link, content }
			value: null,
			previewLoading: false,
			richObject: {},
		}
	},

	computed: {
		getLink() {
			if (!this.value) {
				return ''
			}

			const suffix = this.renderMode === 'content' ? '/content' : ''
			return window.location.protocol + '//' + window.location.host + generateUrl('/apps/tables/#/' + this.value?.type + '/' + this.value?.value + suffix)
		},
	},

	watch: {
		renderMode() {
			this.updateContent()
		},
		value() {
			this.updateRichObject()
			this.updateContent()
		},
	},

	async mounted() {
		if (!this.tablesStore) {
			const { default: store } = await import(
				'../store/store.js')
			const { default: data } = await import(
				'../store/data.js')

			this.tablesStore = store
			this.tablesStore.data = data
		}
	},

	methods: {
		t,
		n,
		async updateContent() {
			if (this.renderMode === 'content') {
				this.previewLoading = true

				await this.loadColumnsForContentPreview()
				await this.loadRowsForContentPreview()

				this.previewLoading = false
			} else {
				delete this.richObject.rows
				delete this.richObject.columns
			}
		},
		selectReference() {
			this.$emit('submit', this.getLink)
		},
		updateRichObject() {
			this.richObject.emoji = this.value.emoji
			this.richObject.link = this.getLink
			this.richObject.ownerDisplayName = this.value.ownerDisplayName
			this.richObject.ownership = this.value.owner
			this.richObject.rowsCount = this.value.rowsCount
			this.richObject.title = this.value.label
			this.richObject.type = this.value.type
			this.richObject.id = this.value.value
		},
		async loadColumnsForContentPreview() {
			if (this.value === null) {
				return
			}

			try {
				const res = await axios.get(generateUrl('/apps/tables/api/1/' + this.value.type + 's/' + this.value.value + '/columns'))
				console.debug('columns from BE', res.data)
				this.richObject.columns = res.data
			} catch (e) {
				displayError(e, t('tables', 'Could not fetch columns for content preview.'))
			}
		},
		async loadRowsForContentPreview() {
			if (this.value === null) {
				return
			}

			try {
				const res = await axios.get(generateUrl('/apps/tables/row/' + this.value.type + '/' + this.value.value))
				console.debug('rows from BE', res.data)
				this.richObject.rows = res.data
			} catch (e) {
				displayError(e, t('tables', 'Could not fetch rows for content preview.'))
			}
		},
	},

}
</script>
<style scoped lang="scss">

	.picker-content {
		width: 100%;
		min-height: 350px;
		display: flex;
		flex-direction: column;
		overflow-y: auto;
		padding: 0 16px 16px 16px;
	}

	.picker-content > h2 {
		margin: 12px 0;
		text-align: center;
	}

	h3 {
		margin: 0;
	}

	.space-B {
		margin-bottom: calc(var(--default-grid-baseline) * 4);
	}

	.space-T {
		margin-top: calc(var(--default-grid-baseline) * 2);
	}

	table {
		width: 100%;
	}

	table th, table td {
		border: none !important;
		width: 50%;
		padding-left: calc(var(--default-grid-baseline) * 2);
		padding-right: calc(var(--default-grid-baseline) * 2);
	}

	.radio {
		display: flex;
	}

	.radio > span {
		padding-right: calc(var(--default-grid-baseline) * 6);
	}

	.radio label .material-design-icon {
		padding-right: calc(var(--default-grid-baseline) * 1);
	}

	.select-button {
		bottom: 0;
		width: 100%;
		display: flex;
		align-items: end;
		flex-direction: column;
		position:sticky;
	}

	.selection-wrapper {
		width: 100%;
	}

	.selection-wrapper .selection {
		margin-left: auto;
		margin-right: auto;
		width: 550px;
	}

	.preview {
		border: 2px solid var(--color-border);
		border-radius: var(--border-radius-large);
	}

</style>
