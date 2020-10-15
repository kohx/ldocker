<template>
    <div class="photo">
        <!-- 画像詳細へのリンク -->
        <!-- 「/photos/{id}」としてルートを作ったので以下のようにリンクを設定 -->
        <RouterLink
            class="photo__overlay"
            :to="{ name: 'photo', params: { id: item.id } }"
            :title="$t('sentence.photo_by', { user: item.user.name })"
        >
            <!-- photo -->
            <figure class="image-wrap">
                <img
                    class="image-wrap__image"
                    :src="item.url"
                    :alt="$t('sentence.photo_by', { user: item.user.name })"
                />
            </figure>
            <!-- photo -->

            <!-- photo controls -->
            <div class="photo__controls">
                <!-- like -->
                <button
                    class="photo__button photo__button--like"
                    :class="{ 'photo__button--liked': is_liked }"
                    title="Like photo"
                    @click.prevent="like"
                >
                    <FAIcon :icon="['fas', 'heart']" class="like fa-fw" />
                </button>
                <div class="photo__total-like">{{ total_like }} </div>
                <!-- /like -->

                <!-- download -->
                <!-- 「@click.stop」でリロードさせない -->
                <a
                    class="photo__button"
                    title="Download photo"
                    @click.stop
                    :href="`/photos/${item.id}/download`"
                >
                    <FAIcon :icon="['fas', 'file-download']" class="fa-fw" />
                </a>
                <!-- /download -->
            </div>
            <!-- /photo controls -->

            <!-- photo username -->
            <div class="photo__username">
                {{ $t("sentence.photo_by", { user: item.user.name }) }}
            </div>
            <!-- /photo username -->
        </RouterLink>
    </div>
</template>

<script>
import { OK } from "../const";

export default {
    // プロップスとして画像オブジェクトをもらう
    props: {
        item: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            is_liked: this.item.is_liked,
            total_like: this.item.total_like,
        };
    },
    methods: {
        /**
         * like
         */
        async like() {
            const isLogin = this.$store.getters["auth/check"];

            if (!isLogin) {
                alert(this.$i18n.tc("sentence.login_before_like"));
                return false;
            }

            // request api ライクを追加する処理
            let response;
            if (this.is_liked) {
                response = await axios.delete(`photos/${this.item.id}/like`);
            } else {
                response = await axios.put(`photos/${this.item.id}/like`);
            }

            // if error
            if (response.status !== OK) {
                // set status to error store
                this.$store.commit("error/setCode", response.status);
                return false;
            }

            this.is_liked = response.data.is_liked;
            this.total_like = response.data.total_like;
        },
    },
};
</script>
