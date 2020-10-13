<template>
    <div class="page">
        <h1>{{ $t("word.home") }}</h1>

        <div class="photo-list">
            <div class="grid">
                <Photo
                    class="grid__item"
                    v-for="photo in photos"
                    :key="photo.id"
                    :item="photo"
                />
            </div>
            <Pagination :current-page="currentPage" :last-page="lastPage" />
        </div>
    </div>
</template>

<script>
import { OK } from "../const";
// コンポーネントをインポート
import Photo from "../components/Photo.vue";
import Pagination from "../components/Pagination.vue";

export default {
    // コンポーネントを登録
    components: {
        Photo,
        Pagination,
    },
    // プロップスとして表示するページをもらう
    props: {
        page: {
            type: Number,
            required: false,
            default: 1,
        },
    },
    data() {
        return {
            // 表示する画像の配列
            photos: [],
            // 現在のページ
            currentPage: 0,
            // 最後のページ
            lastPage: 0,
        };
    },
    methods: {
        /**
         * fetch photos
         * 表示する画像を取得
         */
        async fetchPhotos() {
            // request api
            const response = await axios.get(`photos/?page=${this.page}`);

            if (response.status !== OK) {
                // commit error store
                this.$store.commit("error/setCode", response.status);

                return false;
            }

            // 画像をデータにセット
            this.photos = response.data.data;
            // 現在のページをセット
            this.currentPage = response.data.current_page;
            // 最後のページをデータにセット
            this.lastPage = response.data.last_page;
        },
    },
    watch: {
        // ページネーションで使い回すので $route の監視ハンドラ内で fetchPhotos を実行
        $route: {
            async handler() {
                await this.fetchPhotos();
            },
            // コンポーネントが生成されたタイミングでも実行
            immediate: true,
        },
    },
};
</script>
