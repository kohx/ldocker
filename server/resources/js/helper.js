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

    /**
     * get bottom
     *
     * @param {Function} callback
     */
    static async getBottom(callback) {

        const body = document.body
        const html = document.documentElement

        const scrollHeight = Math.max(
            body.scrollHeight,
            body.offsetHeight,
            html.clientHeight,
            html.scrollHeight,
            html.offsetHeight
        )

        var scrollPositionY = window.innerHeight + window.scrollY

        if (scrollHeight - scrollPositionY <= 0) {
            console.log("on bottom!");
            await callback();
            // window.scrollTo(0, window.scrollY - 5);
        }
    }

    /**
     * fill height
     * 
     * スクロールバーが表示されるまで繰り返す
     * @param {Function} callback
     */
    static async fillHeight(callback) {
        let limit = await callback();

        let viewHeight = document.documentElement.offsetHeight;
        let totalHeight = document.documentElement.scrollHeight;

        if (viewHeight < totalHeight) {
            return;
        }

        for (let page = 2; page <= limit; page++) {

            viewHeight = document.documentElement.offsetHeight;
            totalHeight = document.documentElement.scrollHeight;

            if (viewHeight < totalHeight) {
                break;
            }
            limit = await callback();
        }
    }
}
