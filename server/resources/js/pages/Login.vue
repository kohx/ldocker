<template>
    <div class="container">
        <!-- tabs -->
        <ul class="tab">
            <li
                class="tab__item"
                :class="{'tab__item--active': tab === 1 }"
                @click="tab = 1"
            >{{ $t('word.login') }}</li>
            <li
                class="tab__item"
                :class="{'tab__item--active': tab === 2 }"
                @click="tab = 2"
            >{{ $t('word.register') }}</li>
            <li
                class="tab__item"
                :class="{'tab__item--active': tab === 3 }"
                @click="tab = 3"
            >{{ $t('word.forgot_password') }}</li>
        </ul>
        <!-- /tabs -->

        <!-- login -->
        <section class="login" v-show="tab === 1">
            <h2>{{ $t('word.login') }}</h2>

            <!-- errors -->
            <div v-if="loginErrors" class="errors">
                <ul v-if="loginErrors.email">
                    <li v-for="msg in loginErrors.email" :key="msg">{{ msg }}</li>
                </ul>
                <ul v-if="loginErrors.password">
                    <li v-for="msg in loginErrors.password" :key="msg">{{ msg }}</li>
                </ul>
            </div>
            <!--/ errors -->

            <!-- @submitで login method を呼び出し -->
            <!-- @submitイベントリスナに prevent をつけるとsubmitイベントによってページがリロードさない -->
            <form @submit.prevent="login">
                <div>{{ $t('word.email') }}</div>
                <div>
                    <!-- v-modelでdataをバインド -->
                    <input type="email" v-model="loginForm.email" />
                </div>
                <div>{{ $t('word.password') }}</div>
                <div>
                    <input type="password" v-model="loginForm.password" />
                </div>
                <div>
                    <button type="submit">{{ $t('word.login') }}</button>
                </div>
            </form>

            <h2>{{ $t('word.Socialite') }}</h2>
            <a class="button" href="/login/twitter" title="twitter">twitter</a>
        </section>
        <!-- /login -->

        <!-- register -->
        <section class="register" v-show="tab === 2">
            <h2>{{ $t('word.register') }}</h2>
            <!-- errors -->
            <div v-if="registerErrors" class="errors">
                <ul v-if="registerErrors.name">
                    <li v-for="msg in registerErrors.name" :key="msg">{{ msg }}</li>
                </ul>
                <ul v-if="registerErrors.email">
                    <li v-for="msg in registerErrors.email" :key="msg">{{ msg }}</li>
                </ul>
                <ul v-if="registerErrors.password">
                    <li v-for="msg in registerErrors.password" :key="msg">{{ msg }}</li>
                </ul>
            </div>
            <!--/ errors -->
            <form @submit.prevent="register">
                <div>{{ $t('word.name') }}</div>
                <div>
                    <input type="text" v-model="registerForm.name" />
                </div>
                <div>{{ $t('word.email') }}</div>
                <div>
                    <input type="email" v-model="registerForm.email" />
                </div>
                <div>{{ $t('word.password') }}</div>
                <div>
                    <input type="password" v-model="registerForm.password" />
                </div>
                <div>{{ $t('word.password_confirmation') }}</div>
                <div>
                    <input type="password" v-model="registerForm.password_confirmation" />
                </div>
                <div>
                    <button type="submit">{{ $t('word.register') }}</button>
                </div>
            </form>
        </section>
        <!-- /register -->

        <!-- forgot -->
        <section class="forgot" v-show="tab === 3">
            <h2>forgot</h2>
            <!-- errors -->
            <div v-if="forgotErrors" class="errors">
                <ul v-if="forgotErrors.email">
                    <li v-for="msg in forgotErrors.email" :key="msg">{{ msg }}</li>
                </ul>
            </div>
            <!--/ errors -->
            <form @submit.prevent="forgot">
                <div>{{ $t('word.email') }}</div>
                <div>
                    <input type="email" v-model="forgotForm.email" />
                </div>
                <div>
                    <button type="submit">{{ $t('word.send') }}</button>
                </div>
            </form>
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
                remember: true
            },
            registerForm: {
                name: "",
                email: "",
                password: "",
                password_confirmation: ""
            },
            forgotForm: {
                email: ""
            }
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
        }
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
                this.$router.push("/");
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
                    timeout: 10000
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
                    timeout: 10000
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
        },
        /*
         * clear form
         */
        clearForm() {
            // login form
            this.loginForm.email = "";
            this.loginForm.password = "";
            // register form
            this.registerForm.name = "";
            this.registerForm.email = "";
            this.registerForm.password = "";
            this.registerForm.password_confirmation = "";
            // forgot form
            this.forgot.email = "";
        }
    }
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
