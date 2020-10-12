<template>
    <div class="page">
        <h1>{{ $t("word.login") }}</h1>
        <!-- tabs -->
        <ul class="tab">
            <li
                class="tab__item"
                :class="{ 'tab__item--active': tab === 1 }"
                @click="tab = 1"
            >
                {{ $t("word.login") }}
            </li>
            <li
                class="tab__item"
                :class="{ 'tab__item--active': tab === 2 }"
                @click="tab = 2"
            >
                {{ $t("word.register") }}
            </li>
            <li
                class="tab__item"
                :class="{ 'tab__item--active': tab === 3 }"
                @click="tab = 3"
            >
                {{ $t("word.forgot_password") }}
            </li>
        </ul>
        <!-- /tabs -->

        <!-- login -->
        <section class="login panel" v-show="tab === 1">
            <!-- @submitで login method を呼び出し -->
            <!-- 「:form-errors="loginErrors ? loginErrors.email : []"」でフォーム全体に出すエラーをセット -->
            <FormulateForm
                name="login_form"
                v-model="loginForm"
                @submit="login"
                :form-errors="loginErrors ? loginErrors.email : []"
            >
                <FormulateInput
                    name="email"
                    type="email"
                    :label="$t('word.email')"
                    :validation-name="$t('word.email')"
                    validation="required|email"
                    :placeholder="$t('word.email')"
                />
                <FormulateInput
                    name="password"
                    type="password"
                    :label="$t('word.password')"
                    :validation-name="$t('word.password')"
                    validation="required|min:8"
                    :placeholder="$t('word.password')"
                />
                <FormulateInput type="submit" :disabled="loadingStatus">
                    {{ $t("word.login") }}
                    <FAIcon
                        v-if="loadingStatus"
                        :icon="['fas', 'spinner']"
                        pulse
                        fixed-width
                    />
                </FormulateInput>
            </FormulateForm>

            <h2>{{ $t("word.Socialite") }}</h2>
            <a class="button" href="/login/twitter" title="twitter">
                <FAIcon
                    :icon="['fab', 'twitter']"
                    size="2x"
                    :class="['faa-tada', 'animated-hover']"
                />
            </a>
        </section>
        <!-- /login -->

        <!-- register -->
        <section class="register panel" v-show="tab === 2">
            <!-- 「:errors="registerErrors」これでサーバから返ってくるエラーをFormulateにセットする -->
            <FormulateForm
                name="register_form"
                v-model="registerForm"
                @submit="register"
                :errors="registerErrors"
            >
                <FormulateInput
                    name="name"
                    type="text"
                    :label="$t('word.name')"
                    :validation-name="$t('word.name')"
                    validation="required|max:50"
                    :placeholder="$t('word.name')"
                />
                <FormulateInput
                    name="email"
                    type="email"
                    :label="$t('word.email')"
                    :validation-name="$t('word.email')"
                    validation="required|email"
                    :placeholder="$t('word.email')"
                />
                <FormulateInput
                    name="password"
                    type="password"
                    :label="$t('word.password')"
                    :validation-name="$t('word.password')"
                    validation="required|min:8"
                    :placeholder="$t('word.password')"
                />
                <!-- バリデーション「confirm」はsurfix「_confirm」のまえのnameを探す(password_confirm の場合は password) -->
                <!-- 違うnameで「confirm」する場合は「confirm:password」 のように一致させるフィールドのnameを渡す -->
                <FormulateInput
                    name="password_confirmation"
                    type="password"
                    :label="$t('word.password_confirmation')"
                    :validation-name="$t('word.password_confirmation')"
                    validation="required|confirm:password"
                    :placeholder="$t('word.password_confirmation')"
                />
                <FormulateInput type="submit" :disabled="loadingStatus">
                    {{ $t("word.register") }}
                    <FAIcon
                        v-if="loadingStatus"
                        :icon="['fas', 'spinner']"
                        pulse
                        fixed-width
                    />
                </FormulateInput>
            </FormulateForm>
        </section>
        <!-- /register -->

        <!-- forgot -->
        <section class="forgot panel" v-show="tab === 3">
            <!-- 「:form-errors="forgotErrors ? forgotErrors.email : []"」でフォーム全体に出すエラーをセット -->
            <FormulateForm
                name="forgot_form"
                v-model="forgotForm"
                @submit="forgot"
                :form-errors="forgotErrors ? forgotErrors.email : []"
            >
                <FormulateInput
                    name="email"
                    type="email"
                    :label="$t('word.email')"
                    :validation-name="$t('word.email')"
                    validation="required|email"
                    :placeholder="$t('word.email')"
                />
                <FormulateInput type="submit" :disabled="loadingStatus">
                    {{ $t("word.send") }}
                    <FAIcon
                        v-if="loadingStatus"
                        :icon="['fas', 'spinner']"
                        pulse
                        fixed-width
                    />
                </FormulateInput>
            </FormulateForm>
        </section>
        <!-- /forgot -->
    </div>
</template>

<script>
export default {
    // vueで使うデータ
    data() {
        return {
            tab: 1,
            loginForm: {
                email: "",
                password: "",
                remember: true,
            },
            registerForm: {
                name: "",
                email: "",
                password: "",
                password_confirmation: "",
            },
            forgotForm: {
                email: "",
            },
        };
    },
    // 算出プロパティでストアのステートを参照
    computed: {
        // authストアのapiStatus
        apiStatus() {
            return this.$store.state.auth.apiStatus;
        },
        // authストアのloginErrorMessages
        loginErrors() {
            return this.$store.state.auth.loginErrorMessages;
        },
        // authストアのregisterErrorMessages
        registerErrors() {
            return this.$store.state.auth.registerErrorMessages;
        },
        // authストアのforgotErrorMessages
        forgotErrors() {
            return this.$store.state.auth.forgotErrorMessages;
        },
        // loadingストアのstatus
        loadingStatus() {
            return this.$store.state.loading.status;
        },
    },
    methods: {
        /*
         * login
         */
        async login() {
            // authストアのloginアクションを呼び出す
            await this.$store.dispatch("auth/login", this.loginForm);
            // 通信成功
            if (this.apiStatus) {
                // トップページに移動
                this.$router.push({ name: "home" });
            }
        },
        /*
         * register
         */
        async register() {
            // authストアのregisterアクションを呼び出す
            await this.$store.dispatch("auth/register", this.registerForm);
            // 通信成功
            if (this.apiStatus) {
                // メッセージストアで表示
                this.$store.commit("message/setContent", {
                    // メッセージストアはサーバからのメッセージもあるので「i18n.tc」でコード内で翻訳しておく
                    content: this.$i18n.tc("sentence.sent_verification_email"),
                    timeout: 10000,
                });
                // AUTHストアのエラーメッセージをクリア
                this.clearError();
                // フォームをクリア
                this.clearForm();
            }
        },
        /*
         * forgot
         */
        async forgot() {
            // authストアのforgotアクションを呼び出す
            await this.$store.dispatch("auth/forgot", this.forgotForm);
            if (this.apiStatus) {
                // show message
                this.$store.commit("message/setContent", {
                    // メッセージストアはサーバからのメッセージもあるので「i18n.tc」でコード内で翻訳しておく
                    content: this.$i18n.tc(
                        "sentence.sent_password_reset_email"
                    ),
                    timeout: 10000,
                });
                // AUTHストアのエラーメッセージをクリア
                this.clearError();
                // フォームをクリア
                this.clearForm();
            }
        },
        /*
         * clear error messages
         */
        clearError() {
            // AUTHストアのすべてのエラーメッセージをクリア
            this.$store.commit("auth/setLoginErrorMessages", null);
            this.$store.commit("auth/setRegisterErrorMessages", null);
            this.$store.commit("auth/setForgotErrorMessages", null);

            // ここでformulateもリセットしておく
            this.$formulate.reset("login_form");
            this.$formulate.reset("register_form");
            this.$formulate.reset("forgot_form");
        },
        /*
         * clear form
         */
        clearForm() {
            this.$formulate.reset("login_form");
            this.$formulate.reset("register_form");
            this.$formulate.reset("forgot_form");
        },
    },
};
</script>

<style>
.tab {
    padding: 0;
    display: flex;
    list-style: none;
}
.tab__item {
    border: 1px solid gray;
    padding: 0 0.5rem;
    margin-left: 0.1rem;
    cursor: pointer;
}
.tab__item--active {
    background-color: lightgray;
}
</style>
