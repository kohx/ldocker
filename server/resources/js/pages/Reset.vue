 <template>
    <div class="container--small">
        <h1>{{ $t("word.password_reset") }}</h1>

        <section class="page">
            <!-- errors -->
            <div v-if="resetErrors" class="errors">
                <ul v-if="resetErrors.password">
                    <li v-for="msg in resetErrors.password" :key="msg">
                        {{ msg }}
                    </li>
                </ul>
                <ul v-if="resetErrors.token">
                    <li v-for="msg in resetErrors.token" :key="msg">
                        {{ msg }}
                    </li>
                </ul>
            </div>
            <!--/ errors -->
            <FormulateForm v-model="resetForm" @submit="reset">
                <FormulateInput
                    name="password"
                    type="password"
                    :label="$t('word.password')"
                    :validation-name="$t('word.password')"
                    validation="required|min:8"
                    :placeholder="$t('word.password')"
                />
                <FormulateInput
                    name="password_confirmation"
                    type="password"
                    :label="$t('word.password_confirmation')"
                    :validation-name="$t('word.password_confirmation')"
                    validation="required|min:8"
                    :placeholder="$t('word.password_confirmation')"
                />
                <FormulateInput type="submit" :disabled="loadingStatus">
                    {{ $t("word.reset") }}
                    <FAIcon
                        v-if="loadingStatus"
                        :icon="['fas', 'spinner']"
                        pulse
                        fixed-width
                    />
                </FormulateInput>
            </FormulateForm>
        </section>
    </div>
</template>

    <script>
import Cookies from "js-cookie";

export default {
    // vueで使うデータ
    data() {
        return {
            resetForm: {
                password: "",
                password_confirmation: "",
                token: "",
            },
        };
    },
    computed: {
        // authストアのapiStatus
        apiStatus() {
            return this.$store.state.auth.apiStatus;
        },
        // authストアのresetErrorMessages
        resetErrors() {
            return this.$store.state.auth.resetErrorMessages;
        },
        // loadingストアのstatus
        loadingStatus() {
            return this.$store.state.loading.status;
        },
    },
    methods: {
        /*
         * reset
         */
        async reset() {
            // authストアのresetアクションを呼び出す
            await this.$store.dispatch("auth/reset", this.resetForm);
            // 通信成功
            if (this.apiStatus) {
                // メッセージストアで表示
                this.$store.commit("message/setContent", {
                    content: "パスワードをリセットしました。",
                    timeout: 10000,
                });
                // AUTHストアのエラーメッセージをクリア
                this.clearError();
                // フォームをクリア
                this.clearForm();
                // トップページに移動
                this.$router.push("/");
            }
        },

        /*
         * clear error messages
         */
        clearError() {
            // AUTHストアのエラーメッセージをクリア
            this.$store.commit("auth/setResetErrorMessages", null);
        },

        /*
         * clear reset Form
         */
        clearForm() {
            // reset
            this.resetForm.password = "";
            this.resetForm.password_confirmation = "";
            this.resetForm.token = "";
        },
    },
    created() {
        // クッキーからリセットトークンを取得
        const token = Cookies.get("RESETTOKEN");

        // リセットトークンがない場合はルートページへ移動させる
        if (this.resetForm.token == null) {
            // move to home
            this.$router.push("/");
        }

        // フォームにリセットトークンをセット
        this.resetForm.token = token;

        // リセットトークンをクッキーから削除
        if (token) {
            Cookies.remove("RESETTOKEN");
        }
    },
};
</script>
