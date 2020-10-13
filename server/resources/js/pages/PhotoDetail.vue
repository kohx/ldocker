<template>
    <!-- photo-detail -->
    <div
        v-if="photo"
        class="photo-detail"
        :class="{ 'photo-detail--column': fullWidth }"
    >
        <!-- pane image -->
        <figure
            class="photo-detail__pane photo-detail__image"
            @click="fullWidth = !fullWidth"
        >
            <img :src="photo.url" alt />
            <figcaption>Posted by {{ photo.user.name }}</figcaption>
        </figure>
        <!-- /pane image -->
    </div>
    <!-- /photo-detail -->
</template>

<script>
import { OK, CREATED, UNPROCESSABLE_ENTITY } from "../const";

export default {
    props: {
        // routeで設定された :id の値が入る
        id: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            loading: false,
            photo: null,
            fullWidth: false,
        };
    },
    computed: {
        isLogin() {
            return this.$store.getters["auth/check"];
        },
    },
    methods: {
        async fetchPhoto() {
            // api request
            const response = await axios.get(`photos/${this.id}`);

            // error
            if (response.status !== OK) {
                // commit errer store
                this.$store.commit("error/setCode", response.status);
                return false;
            }

            // set photo data
            this.photo = response.data;
        },
    },
    watch: {
        // 詳細ページで使い回すので $route の監視ハンドラ内で fetchPhoto を実行
        $route: {
            async handler() {
                await this.fetchPhoto();
            },
            // コンポーネントが生成されたタイミングでも実行
            immediate: true,
        },
    },
};
</script>
