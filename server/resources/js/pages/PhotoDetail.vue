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
            <img :src="photo.url" :alt="$t('sentence.photo_by', { user: photo.user.name })" />
            <figcaption>
                {{ $t("sentence.photo_by", { user: photo.user.name }) }}
            </figcaption>
        </figure>
        <!-- /pane image -->

        <!-- pane -->
        <div class="photo-detail__pane">
            <!-- like -->
            <button
                class="photo__button photo-detail__button--like"
                :class="{ 'photo-detail__button--liked': photo.is_liked }"
                title="Like photo"
                @click="like"
            >
                <FAIcon :icon="['fas', 'heart']" class="fa-fw" />
                {{ photo.total_like }}
            </button>
            <!-- /like -->

            <!-- download -->
            <a
                :href="`/photos/${photo.id}/download`"
                class="button"
                title="Download photo"
            >
                <FAIcon :icon="['fas', 'file-download']" class="fa-fw" />{{
                    $t("word.download")
                }}
            </a>
            <!-- /download -->

            <!-- comment -->
            <h2 class="photo-detail__title">
                <FAIcon :icon="['far', 'comment-dots']" class="fa-fw" />{{
                    $t("word.comments")
                }}
            </h2>
            <!-- comment list -->
            <ul v-if="photo.comments.length > 0" class="photo-detail__comments">
                <li
                    v-for="comment in photo.comments"
                    :key="comment.content"
                    class="photo-detail__comment-item"
                >
                    <p class="photo-detail__comment-body">
                        {{ comment.content }}
                    </p>
                    <p class="photo-detail__comment-info">
                        {{ comment.user.name }}
                    </p>
                </li>
            </ul>
            <p v-else>{{ $t("sentence.no_comments_yet") }}</p>
            <!-- /comment list -->
            <form v-if="isLogin" @submit.prevent="addComment" class="form">
                <!-- error message -->
                <div v-if="commentErrors" class="errors">
                    <ul v-if="commentErrors.content">
                        <li v-for="msg in commentErrors.content" :key="msg">
                            {{ msg }}
                        </li>
                    </ul>
                </div>
                <!-- /error message -->
                <FormulateInput
                    type="textarea"
                    v-model="commentContent"
                    label="Enter a comment in the box"
                    validation="required|max:100,length"
                    validation-name="comment"
                    error-behavior="live"
                    :help="
                        $t('sentence.keep_it_under_100_characters_left', {
                            length: 100 - commentContent.length,
                        })
                    "
                />
                <FormulateInput type="submit" :disabled="loadingStatus">
                    {{ $t("sentence.submit_comment") }}
                    <FAIcon
                        v-if="loadingStatus"
                        :icon="['fas', 'spinner']"
                        class="fa-fw"
                        pulse
                    />
                </FormulateInput>
            </form>
            <!-- /comment -->
        </div>
        <!-- /pane -->
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
            commentContent: "",
            commentErrors: null,
        };
    },
    computed: {
        isLogin() {
            return this.$store.getters["auth/check"];
        },
        // loadingストアのstatus
        loadingStatus() {
            return this.$store.state.loading.status;
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
        async like() {
            const isLogin = this.$store.getters["auth/check"];

            if (!isLogin) {
                alert(this.$i18n.tc("sentence.login_before_like"));
                return false;
            }

            // request api ライクを追加する処理
            let response;
            if (this.photo.is_liked) {
                response = await axios.delete(`photos/${this.photo.id}/like`);
            } else {
                response = await axios.put(`photos/${this.photo.id}/like`);
            }

            // if error
            if (response.status !== OK) {
                // set status to error store
                this.$store.commit("error/setCode", response.status);
                return false;
            }

            this.photo.is_liked = response.data.is_liked;
            this.photo.total_like = response.data.total_like;
        },
        async addComment() {
            /*
             * request api
             */
            const response = await axios.post(`photos/${this.id}/comments`, {
                content: this.commentContent,
            });
            /*
             * バリデーションエラー
             */
            if (response.status === UNPROCESSABLE_ENTITY) {
                // set validation error
                this.commentErrors = response.data.errors;
                return false;
            }
            // clear comment content
            this.commentContent = "";
            // エラーメッセージをクリア
            this.commentErrors = null;
            /*
             * その他のエラー
             */
            if (response.status !== CREATED) {
                // set status to error store
                this.$store.commit("error/setCode", response.status);
                return false;
            }
            // 投稿し終わったあとに一覧に投稿したてのコメントを表示
            this.photo.comments = [response.data, ...this.photo.comments];
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
