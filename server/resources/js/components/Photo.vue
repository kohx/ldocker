<template>
    <div class="photo">
        <!-- 画像詳細へのリンク -->
        <!-- 「/photos/{id}」としてルートを作ったので以下のようにリンクを設定 -->
        <RouterLink
            class="photo__overlay"
            :to="{ name: 'photo', params: { id: item.id } }"
            :title="`View the photo by ${item.user.name}`"
        >
            <!-- 画像 -->
            <figure class="image-wrap">
                <img
                    class="image-wrap__image"
                    :src="item.url"
                    :alt="`Photo by ${item.user.name}`"
                />
            </figure>
            <!-- 画像 -->

            <!-- photo username -->
            <div class="photo__username">{{ item.user.name }}</div>
            <!-- /photo username -->
        </RouterLink>
    </div>
</template>

<script>
export default {
    // プロップスとして画像オブジェクトをもらう
    props: {
        item: {
            type: Object,
            required: true,
        },
    },
};
</script>
