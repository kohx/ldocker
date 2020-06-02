<template>
  <div class="container">

    <!-- tabs -->
    <ul class="tab">
      <li class="tab__item"
        :class="{'tab__item--active': tab === 1 }"
        @click="tab = 1"
      >Login</li>
      <li class="tab__item"
         :class="{'tab__item--active': tab === 2 }"
          @click="tab = 2"
        >Register</li>
      <li
        class="tab__item"
        :class="{'tab__item--active': tab === 3 }"
        @click="tab = 3"
      >Forgot password ?</li>
    </ul>
    <!-- /tabs -->

    <!-- login -->
    <section
      class="login"
      v-show="tab === 1"
    >
      <h2>Login</h2>
      <!-- @submitで login method を呼び出し -->
      <!-- @submitイベントリスナに prevent をつけるとsubmitイベントによってページがリロードさない -->
      <form @submit.prevent="login">
        <div>Email</div>
        <div>
          <!-- v-modelでdataをバインド -->
          <input type="email" v-model="loginForm.email" />
        </div>
        <div>Password</div>
        <div>
          <input type="password" v-model="loginForm.password" />
        </div>
        <div>
          <button type="submit">login</button>
        </div>
      </form>
    </section>
    <!-- /login -->

    <!-- register -->
    <section class="register" v-show="tab === 2">
      <h2>register</h2>
      <form @submit.prevent="register">
        <div>Name</div>
        <div>
          <input type="text" v-model="registerForm.name" />
        </div>
        <div>Email</div>
        <div>
          <input type="email" v-model="registerForm.email" />
        </div>
        <div>Password</div>
        <div>
          <input type="password" v-model="registerForm.password" />
        </div>
        <div>Password confirmation</div>
        <div>
          <input type="password" v-model="registerForm.password_confirmation" />
        </div>
        <div>
          <button type="submit">register</button>
        </div>
      </form>
    </section>
    <!-- /register -->

    <!-- forgot -->
    <section class="forgot" v-show="tab === 3">
      <h2>forgot</h2>
      <form @submit.prevent="forgot">
        <div>Email</div>
        <div>
          <input type="email" v-model="forgotForm.email" />
        </div>
        <div>
          <button type="submit">send</button>
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
        },
    };
  },
   methods: {
       /*
        * login
        */
        async login() {
            alert("login");
            this.clearForm();
        },
       /*
        * register
        */
        async register() {
            alert("register");
            this.clearForm();
        },
       /*
        * forgot
        */
        async forgot() {
            alert("forgot");
            this.clearForm();
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
        },
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
