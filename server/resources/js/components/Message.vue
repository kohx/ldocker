<template>
    <div class="message" v-show="message">{{ message }}</div>
</template>

<script>
export default {
    computed: {
        // Messageストアのcontentステートを取得
        message() {
            return this.$store.state.message.content;
        },
    }
};
</script>
