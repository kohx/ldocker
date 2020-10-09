<template>
    <FormulateForm
        name="comment_form"
        v-model="commentForm"
        @submit="sendComment"
        :form-errors="commentErrors"
    >
        <FormulateInput
            name="content"
            type="textarea"
            :value="comentContent"
            :label="label"
            validation="required|max:100,length"
            :validation-name="$t('word.comment_content')"
            :help="$t('sentence.keep_it_under_characters', {length: 100 - commentForm.content.length})"
            class="photo-detail__comment-input"
        />

        <FormulateInput type="submit" :disabled="loadingStatus">
            {{buttonName}}
            <FAIcon v-if="loadingStatus" :icon="['fas', 'spinner']" fixed-width pulse />
        </FormulateInput>
    </FormulateForm>
</template>

<script>
import { OK, CREATED, UNPROCESSABLE_ENTITY } from "../const";

export default {
    props: {
        label: {
            type: String,
            default: function () {
                return this.$i18n.tc("word.comment_content");
            },
        },
        photoId: {
            type: String,
            required: true,
        },
        commentId: {
            type: Number,
            default: null,
        },
        comentContent: {
            type: String,
            default: "",
        },
        buttonName: {
            type: String,
            default: function () {
                return this.$i18n.tc("word.submit_comment");
            },
        },
    },
    data() {
        return {
            commentForm: {
                content: "",
            },
            commentErrors: [],
        };
    },
    computed: {
        // loadingストアのstatus
        loadingStatus() {
            return this.$store.state.loading.status;
        },
    },
    methods: {
        async sendComment() {
            if (this.commentId === null) {
                this.addComment();
            } else {
                this.changeComment();
            }
        },
        async addComment() {
            // エラーメッセージをクリア
            this.commentErrors = [];

            // request api
            const response = await axios.post(
                `photos/${this.photoId}/comments`,
                this.commentForm
            );

            // バリデーションエラー
            if (response.status === UNPROCESSABLE_ENTITY) {
                // set validation error
                this.commentErrors = response.data.errors.content;
                return false;
            }

            // 成功
            if (response.status === CREATED) {
                // レスポンスで帰ってきたコメントデータをemitで返す
                this.$emit("created", response.data);

                // クリア
                this.$formulate.reset("comment_form");
                this.commentForm.content = "";
            } else {
                // エラー時はformに表示
                this.commentErrors = [this.$i18n.tc("sentence.send_comment_failed")];
            }
        },
        async changeComment() {
            // エラーメッセージをクリア
            this.commentErrors = [];

            // request api
            const response = await axios.put(
                `photos/comments/${this.commentId}`,
                this.commentForm
            );

            // バリデーションエラー
            if (response.status === UNPROCESSABLE_ENTITY) {
                // set validation error
                this.commentErrors = response.data.errors.content;
                return false;
            }

            // 成功
            if (response.status === OK) {
                // レスポンスで帰ってきたコメントデータをemitで返す
                this.$emit("created", response.data);

                // クリア
                this.commentForm.content = "aaa";
                this.$formulate.reset("comment_form");
            } else {
                // エラー時はformに表示
                this.commentErrors = [this.$i18n.tc("sentence.send_comment_failed")];
            }
        },
    },
};
</script>
