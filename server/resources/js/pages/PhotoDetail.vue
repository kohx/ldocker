<template>
    <div v-if="item">
        {{user.id}}
        <aside class="photo-detail__aside">
            <figure class="photo-detail__figure">
                <!-- <img class="photo-detail__image" :src="item.url" :alt="`${item.name}`" /> -->
            </figure>
            <figcaption class="photo-detail__caption">
                <span class="photo-detail__user">
                    <FAIcon :icon="['fas', 'image']" fixed-width />
                    {{$t('sentence.posted_by', {username: item.user.name})}}
                    <FAIcon :icon="['fas', 'clock']" fixed-width />
                    {{ $d(new Date(item.user.updated_at), 'full') }}
                </span>

                <!-- like button -->
                <FAIcon
                    :icon="['fas', 'heart']"
                    class="fa-fw button__like"
                    @click.prevent="onLike"
                    :class="{'--liked': item.is_liked, '--active': isLikeSending}"
                />
                {{item.total_like}}
                <!-- /like button -->

                <!-- down load link -->
                <a class="button__down-load" @click.stop :href="`/photos/${item.id}/download`">
                    <FAIcon :icon="['fas', 'file-download']" fixed-width />
                </a>
                <!-- /down load link -->
            </figcaption>
        </aside>

        <main>
            <h1 class="sub-title">{{item.name}}</h1>
            <p>{{item.description}}</p>
        </main>

        <div class="photo-detail__comments">
            <h2 class="photo-detail__comment-title">
                <FAIcon :icon="['far', 'comment-dots']" fixed-width />
                {{$t('word.comments')}}
            </h2>
            <!-- comment form -->
            <CommentForm v-if="isLogin && activeComment === null" @created="createdComment" :photo-id="id" />
            <!-- /comment form -->

            <!-- comment list -->
            <ul v-if="item.comments.length > 0" class="photo-detail__comment-list">
                <li
                    v-for="comment in item.comments"
                    :key="comment.id"
                    class="photo-detail__comment-item"
                >
                    <p v-if="activeComment !== comment.id" class="photo-detail__comment-body">{{ comment.content }}</p>
                    <!-- <CommentForm v-if="isLogin && activeComment === comment.id" @created="createdComment" :photo-id="id" :comment-id="comment.id" :coment-content="comment.content" :button-name="$t('word.change')" /> -->
                    <CommentForm @created="updatedComment" :photo-id="id" :comment-id="comment.id" :coment-content="comment.content" :button-name="$t('word.change')" />

                    <div class="photo-detail__comment-info">
                        <span v-if="user.id === comment.user.id">
                            <FAIcon
                                @click="EditComment(comment.id)"
                                :icon="['far', 'edit']"
                                fixed-width
                            />
                            <FAIcon :icon="['far', 'trash-alt']" fixed-width />
                        </span>
                        {{comment.user.id}}: {{ comment.user.name }} {{ $d(new Date(comment.updated_at), 'full') }}
                    </div>
                </li>
            </ul>
            <p v-else>{{$t('sentence.no_comments_yet')}}</p>
            <!-- /comment list -->
        </div>
    </div>
</template>

<script>
import { OK, CREATED, UNPROCESSABLE_ENTITY } from "../const";
import CommentForm from "../components/CommentForm";

export default {
    components: {
        CommentForm,
    },
    props: {
        // routeで設定された :id の値が入る
        id: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            item: null,
            isLikeSending: false,
            activeComment: null
        };
    },
    computed: {
        // authストアのuser
        user() {
            return this.$store.state.auth.user;
        },
        isLogin() {
            return this.$store.getters["auth/check"];
        },
        // loadingストアのstatus
        loadingStatus() {
            return this.$store.state.loading.status;
        },
    },
    methods: {
        async getPhoto() {
            // api request
            const response = await axios.get(`photos/${this.id}`);

            // error
            if (response.status !== OK) {
                // commit errer store
                this.$store.commit("error/setCode", response.status);
                return false;
            }

            // set photo data
            this.item = response.data;
        },
        /**
         * on like click
         */
        async onLike() {
            if (!this.isLogin) {
                // メッセージ
                this.$store.commit("message/setContent", {
                    content: this.$i18n.tc(
                        "sentence.please_login_to_use_like_feature"
                    ),
                    timeout: 6000,
                });
                return false;
            }

            if (this.loadingStatus) {
                return;
            }

            this.isLikeSending = true;
            const response = await axios.put(`photos/like`, {
                photo_id: this.item.id,
                liked: !this.item.is_liked,
            });

            this.isLikeSending = false;

            // アップデート成功
            if (response.status == OK) {
                this.item.is_liked = response.data.is_liked;
                this.item.total_like = response.data.total_like;
            }
        },
        EditComment(commentId) {
            this.activeComment = commentId;
        },
        createdComment(newComment) {
            // emitで返ってきたコメントオブジェクトをマージ
            this.item.comments = [newComment, ...this.item.comments];
        },
        updatedComment(newComment) {
            // emitで返ってきたコメントオブジェクトをマージ
            console.log(newComment);
            // this.item.comments = [newComment, ...this.item.comments];
        },
    },
    watch: {
        $route: {
            async handler() {
                await this.getPhoto();
            },
            immediate: true,
        },
    },
};
</script>

<style scoped>
.photo-detail__figure {
    margin: 0 -2rem;
}

.photo-detail__image {
    width: 100%;
}

.photo-detail__caption {
    display: flex;
    padding: 1rem;
    align-items: center;
}

.photo-detail__user {
    margin-right: auto;
}

.button__down-load,
.button__like {
    color: gray;
    opacity: 1;
    transition: all ease-in-out 0.3s;
}

/* ライクしてある */

.button__like.--liked {
    color: tomato;
    opacity: 1;
}

/* ライク追加中 */

.button__like.--active {
    opacity: 0;
    transform: scale(3);
}

/* ライク削除中 */

.button__like.--liked.--active {
    opacity: 1;
    transform: scale(1);
}

.photo-detail__comment-list {
    margin-bottom: 2rem;
}

.photo-detail__comment-item {
    border-bottom: thin solid lightsteelblue;
    margin-bottom: 2rem;
}

.photo-detail__comment-info {
    text-align: right;
    font-size: 0.8em;
}
</style>
