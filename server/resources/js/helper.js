export default class Helper {

    static capitalizeFirstLetter(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /**
     * get language
     * ブラウザから言語を取得
     *
     */
    static getLanguage() {
        const language = (window.navigator.languages && window.navigator.languages[0]) ||
            window.navigator.language ||
            window.navigator.userLanguage ||
            window.navigator.browserLanguage;
        return language.slice(0, 2);
    }
}
