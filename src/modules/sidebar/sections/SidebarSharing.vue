<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div v-if="activeElement" class="sharing">
		<div v-if="canShareElement(activeElement)">
			<ShareForm :shares="shares" @add="addShare" @update="updateShare" />
			<ShareList :shares="shares" @remove="removeShare" @update="updateShare" />
		</div>
	</div>
</template>

<script>
import { useTablesStore } from '../../../store/store.js'
import { mapState, mapActions } from 'pinia'
import shareAPI from '../mixins/shareAPI.js'
import ShareForm from '../partials/ShareForm.vue'
import ShareList from '../partials/ShareList.vue'
import { getCurrentUser } from '@nextcloud/auth'
import permissionsMixin from '../../../shared/components/ncTable/mixins/permissionsMixin.js'

export default {
	components: {
		ShareForm,
		ShareList,
	},

	mixins: [shareAPI, permissionsMixin],
	data() {
		return {
			loading: false,

			// shared with
			shares: [],
		}
	},

	computed: {
		...mapState(useTablesStore, ['activeElement', 'isView']),
	},

	watch: {
		activeElement() {
			if (this.activeElement) {
				this.loadSharesFromBE()
			}
		},
	},

	mounted() {
		if (this.activeElement) {
			this.loadSharesFromBE()
		}
	},

	methods: {
		...mapActions(useTablesStore, ['setTableHasShares', 'setViewHasShares']),
		getCurrentUser,
		async loadSharesFromBE() {
			this.loading = true
			this.shares = await this.getSharedWithFromBE()
			this.loading = false
		},
		async removeShare(share) {
			await this.removeShareFromBE(share.id)
			await this.loadSharesFromBE()
			// If no share is left, remove shared indication
			if (this.isView) {
				if (this.shares.find(share => ((share.nodeType === 'view' && share.nodeId === this.activeElement.id) || (share.nodeType === 'table' && share.nodeId === this.activeElement.tableId))) === undefined) {
					await this.setViewHasShares({ viewId: this.activeElement.id, hasShares: false })
				}
			} else {
				if (this.shares.find(share => (share.nodeType === 'table' && share.nodeId === this.activeElement.id)) === undefined) {
					await this.setTableHasShares({ tableId: this.activeElement.id, hasShares: false })
				}
			}
		},
		async addShare(share) {
			await this.sendNewShareToBE(share)
			await this.loadSharesFromBE()
		},
		async updateShare(data) {
			const shareId = data.id
			delete data.id
			await this.updateShareToBE(shareId, data)
			await this.loadSharesFromBE()
		},
	},
}
</script>
