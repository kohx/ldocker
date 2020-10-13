<template>
    <div class="pagination">
        <!-- 前へのリンク -->
        <!-- urlを引き継ぐので以下のように設定 -->
        <!-- 現在のページはクエリストリング「page」で指定 -->
        <RouterLink
            v-if="!isFirstPage"
            :to="`/?page=${currentPage - 1}`"
            class="button"
            >&laquo; prev</RouterLink
        >
        <!-- 次へのリンク -->
        <RouterLink
            v-if="!isLastPage"
            :to="`/?page=${currentPage + 1}`"
            class="button"
            >next &raquo;</RouterLink
        >
    </div>
</template>

<script>
export default {
    // プロップスとして現在のページとラストページをもらう
    props: {
        currentPage: {
            type: Number,
            required: true,
        },
        lastPage: {
            type: Number,
            required: true,
        },
    },
    computed: {
        // クエリストリングで渡されたページが最初のページかチェック
        isFirstPage() {
            return this.currentPage === 1;
        },
        // クエリストリングで渡されたページが最後のページかチェック
        isLastPage() {
            return this.currentPage === this.lastPage;
        },
    },
};
</script>
