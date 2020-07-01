// 言語ファイルをインポート
import {contents as en} from "./En";
import {contents as ja} from "./Ja";

// 言語アイテムをマージ
const messages = {
    en: en.messages,
    ja: ja.messages
};

// 日付フォーマットをマージ
const dateTimeFormats = {
    en: en.dateTimeFormats,
    ja: ja.dateTimeFormats
};

// ナンバーフォーマットをマージ
const numberFormats = {
    en: en.numberFormats,
    ja: ja.numberFormats
};

// それぞれをエクスポート
export { messages };
export { dateTimeFormats };
export { numberFormats };
