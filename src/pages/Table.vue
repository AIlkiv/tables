<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="main-table-view">
		<MainWrapper :element="activeTable" :is-view="false" />
		<MainModals />
	</div>
</template>

<script>
import { mapState } from 'pinia'
import { useTablesStore } from '../store/store.js'
import MainWrapper from '../modules/main/sections/MainWrapper.vue'
import MainModals from '../modules/modals/Modals.vue'

export default {
	components: {
		MainWrapper,
		MainModals,
	},

	computed: {
		...mapState(useTablesStore, ['activeTableId', 'activeTable']),
	},

	watch: {
		activeTableId() {
			if (this.activeTableId && !this.activeTable) {
				// table does not exists, go to startpage
				this.$router.push('/').catch(err => err)
			}
		},
		activeTable() {
			if (this.activeTableId && !this.activeTable) {
				// table does not exists, go to startpage
				this.$router.push('/').catch(err => err)
			}
		},
	},
}
</script>
<style lang="scss">
.main-table-view {
	width: max-content;
	min-width: var(--app-content-width, 100%);
}

@page {
	size: auto;
	margin: 5mm;
}

@media print {
	html, body {
		background: var(--color-main-background, white) !important;
	}

	html {
		overflow-y: scroll;
	}

	body {
		position: absolute;
	}

	/* hide toast notifications for printing */
	.toastify.dialogs {
		display: none;
	}

	.app-navigation {
		display: none !important;
	}

	#header {
		display: none !important;
	}

	#content-vue {
		display: block !important;
		position: static;
		overflow: auto;
		height: auto;
	}

	#content-vue .row.first-row {
		padding-left: 0px;
	}

	.main-table-view, .main-view-view {
		width: auto;
		min-width: 0;
	}

	.table-dashboard {
		display: none !important;
	}

	.row.space-T {
		display: none !important;
	}

	#app-content-vue .options.row {
		display: none !important;
	}

	#app-content-vue table {
		table-layout: fixed;
	}

	#app-content-vue table td, #app-content-vue table th {
		white-space: normal !important;
		word-break: normal !important;
		word-wrap: break-word !important;
		width: auto !important;
	}

	#app-content-vue table th .clickable {
		overflow-wrap: anywhere;
	}

	#app-content-vue table th .menu {
		display: none !important;
	}

	#app-content-vue table td .tiptap-reader-cell {
		max-height: fit-content;
		max-width: fit-content;
	}

	#app-content-vue table tr > th.sticky:first-child,
	#app-content-vue table tr > td.sticky:first-child,
	#app-content-vue table tr > th.sticky:last-child,
	#app-content-vue table tr > td.sticky:last-child {
		display: none !important;
	}
}
</style>
