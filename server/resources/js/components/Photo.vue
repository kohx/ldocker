<template>
    <div class="photo-card" :class="{squere: isSquare}">
        <RouterLink class="photo-card__overlay" :to="{name: 'photo-detail', params: {id: item.id}}">
            <aside class="photo-card__aside">
                <figure class="photo-card__figure" :style="`background-image:url(${item.url})`">
                    <!-- <figure class="photo-card__figure" :style="`background-color:gray`"> -->
                    <img class="photo-card__image" :src="item.url" :alt="`${item.name}`" />
                </figure>
            </aside>

            <div class="photo-card__main">
                <!-- content -->
                <div class="photo-card__content">
                    <h2 class="photo-card__title">{{item.name}}</h2>
                    <p class="photo-card__copy">{{item.description}}</p>
                    <div
                        class="photo-card__footer"
                    >{{ item.user.name }} {{ $d(new Date(item.user.updated_at), 'full') }}</div>
                </div>
                <!-- /content -->

                <!-- controls -->
                <div class="photo-card__controls">
                    <!-- like button -->
                    <FAIcon
                        :icon="['fas', 'heart']"
                        class="fa-fw button__like"
                        @click.prevent="onLike"
                        :class="{'--liked': item.is_liked, '--active': isActive}"
                    />
                    {{item.total_like}}
                    <!-- /like button -->

                    <!-- down load link -->
                    <a class="button__down-load" @click.stop :href="`/photos/${item.id}/download`">
                        <FAIcon :icon="['fas', 'file-download']" fixed-width />
                    </a>
                    <!-- /down load link -->
                </div>
                <!-- /controls -->
            </div>
        </RouterLink>
    </div>
</template>

<script>
import { OK } from "../const";

export default {
    data() {
        return {
            isActive: false,
        };
    },
    props: {
        item: {
            type: Object,
            required: true,
        },
        isSquare: {
            type: Boolean,
            default: true,
        },
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

            this.isActive = true;
            const response = await axios.put(`photos/like`, {
                photo_id: this.item.id,
                liked: !this.item.is_liked,
            });
            this.isActive = false;
            // アップデート成功
            if (response.status == OK) {
                this.item.is_liked = response.data.is_liked;
                this.item.total_like = response.data.total_like;
            }
        },
    },
};
</script>

<style scoped>
.photo-card__overlay {
    display: flex;
    text-decoration: none;
    color: inherit;
    border-bottom: 1px solid rgba(0, 0, 0, 0.2);;
}

.photo-card__aside {
    width: 30%;
}

.photo-card__main {
    width: 70%;
    margin: 0 0 0 1rem;
    position: relative;
}

.photo-card__controls {
    position: absolute;
    right: 0;
    top: 0;
}

.photo-card__footer {
    text-align: right;
}

.photo-card__figure {
    display: block;
    height: 0;
    width: 100%;
    padding-bottom: 100%;
    background-size: cover;
    background-position: center center;
}

.photo-card__image {
    display: none;
}

.photo-card__title {
    display: block;
    margin: 0;
}

.button__down-load,
.button__like {
    color: gray;
    opacity: 1;
    transition: all ease-in-out 0.3s;
}

/* grid */
.squere .photo-card__overlay {
    border-bottom: none;
    position: relative;
}

.squere .photo-card__main {
    width: inherit;
    position: absolute;
    margin: 0;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    overflow: hidden;
    background-color: rgba(0, 0, 0, 0.2);
}

.squere .photo-card__aside {
    width: 100%;
}

.squere .photo-card__body {
    position: relative;
}

.squere .photo-card__controls {
    right: 1rem;
    top: 1rem;
}

.squere .photo-card__content {
    position: absolute;
    left: 0;
    bottom: 0;
    right: 0;
    padding: 1rem;
    color: white;
}
.squere .photo-card__copy {
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}

.squere .button__down-load,
.squere .button__like {
    color: white;
}

/* ライクしてある */
.button__like.--liked,
.squere.button__like.--liked {
    color: tomato;
    opacity: 1;
}

/* ライク追加中 */
.button__like.--active,
.squere .button__like.--active {
    opacity: 0;
    transform: scale(3);
}

/* ライク削除中 */
.button__like.--liked.--active,
.squere .button__like.--liked.--active {
    opacity: 1;
    transform: scale(1);
}
</style>
