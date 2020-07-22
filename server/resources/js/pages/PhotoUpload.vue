<template>
    <div class="page">
        <h1>{{ $t('word.upload_photo') }}</h1>

        <FormulateForm
            @submit="uploadPhoto"
            name="upload_form"
            v-model="photoForm"
            :form-errors="uploadErrors"
        >
            <FormulateInput
                type="text"
                name="photo_name"
                :label="$t('word.photo_name')"
                :validation-name="$t('word.photo_name')"
                validation="required|max:255,length"
            />
            <FormulateInput
                type="textarea"
                name="photo_description"
                :label="$t('word.photo_description')"
                :validation-name="$t('word.photo_description')"
                validation="max:255,length"
            />
            <FormulateInput
                name="photo_files"
                type="image"
                :label="$t('word.photo_files')"
                :validation-name="$t('word.photo_files')"
                validation="required|mime:image/jpeg,image/png,image/gif|max_photo:3"
                upload-behavior="delayed"
                :uploader="uploader"
                multiple
            />

            <FormulateInput type="submit" :disabled="loadingStatus">
                {{ $t('word.upload') }}
                <FAIcon v-if="loadingStatus" :icon="['fas', 'spinner']" pulse fixed-width />
            </FormulateInput>
        </FormulateForm>
    </div>
</template>

<script>
import { CREATED, UNPROCESSABLE_ENTITY, INTERNAL_SERVER_ERROR } from "../const";

export default {
    data() {
        return {
            photoForm: {
                photo_files: null,
                photo_name: "",
                photo_description: ""
            },
            uploadErrors: []
        };
    },
    // 算出プロパティでストアのステートを参照
    computed: {
        // loadingストアのstatus
        loadingStatus() {
            return this.$store.state.loading.status;
        }
    },
    methods: {
        // ダミーアップローダ
        uploader: function(file, progress) {
            // ここで処理しないで、uploadPhotoメソッドで処理する
            // プログレスの進行を100%にするのみ
            progress(100);
            return Promise.resolve({});
        },
        async uploadPhoto() {
            // フォームデータオブジェクトを作成
            const formData = new FormData();

            // フォームデータにファイルオブジェクトを追加
            let c = 0;
            this.photoForm.photo_files.files.forEach(item => {
                formData.append(`photo_files[${c++}]`, item.file);
            });

            // その他の値をセット
            formData.append("photo_name", this.photoForm.photo_name);
            formData.append(
                "photo_description",
                this.photoForm.photo_description
            );

            // Api リクエスト
            const response = await axios.post("photos", formData);

            // アップデート成功
            if (response.status === CREATED) {
                // 成功時はメッセージを出す
                this.$store.commit("message/setContent", {
                    content: response.data.message,
                    timeout: 6000
                });

                // フォームのクリア
                this.photoForm = {
                    photo_files: null,
                    photo_name: "",
                    photo_description: ""
                };
                // バリデーションのクリア
                this.$formulate.reset("upload_form");
            } else {
                // エラー時はformに表示
                this.uploadErrors = response.data.errors;
            }
        }
    }
};
</script>
