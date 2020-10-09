<template>
    <div class="page">
        <h1>
            {{ $t('word.photo') }}
            <span class="title__button-box">
                <FAIcon
                    :icon="['fas', 'grip-horizontal']"
                    fixed-width
                    :class="{'--active': isSquare}"
                    border
                    @click="isSquare = true"
                />
                <FAIcon
                    :icon="['fas', 'grip-lines']"
                    fixed-width
                    :class="{'--active': !isSquare}"
                    border
                    @click="isSquare = false"
                />
            </span>
        </h1>
        <div :class="{grid: isSquare}">
            <Photo
                class="grid__item"
                v-for="photo in photos"
                :key="photo.id"
                :item="photo"
                :is-square="isSquare"
            />
        </div>
        <div v-show="loadingStatus" class="loading">
            <FAIcon :icon="['fas', 'sync']" spin />
        </div>
    </div>
</template>

<script>
import { OK } from "../const";
import Photo from "../components/Photo";
import Helper from "../helper";

export default {
    components: {
        Photo,
    },
    data() {
        return {
            photos: [],
            currentPage: 0,
            lastPage: 1,
            isSquare: true,
        };
    },
    computed: {
        // loadingストアのstatus
        loadingStatus() {
            return this.$store.state.loading.status;
        },
    },
    methods: {
        /**
         * fetch photos
         */
        async fetchPhotos() {
            if (this.loadingStatus) {
                return;
            }

            let page =
                this.lastPage > this.currentPage ? this.currentPage + 1 : false;

            if (!page) {
                return;
            }

            // request api
            const response = await axios.get(`photos/?page=${page}`);

            if (response.status !== OK) {
                // commit error store
                this.$store.commit("error/setCode", response.status);

                return false;
            }

            // set photo datas
            this.photos = this.photos.concat(response.data.data);
            this.currentPage = response.data.current_page;
            this.lastPage = response.data.last_page;

            return this.lastPage;
        },
        async getBottomWraper() {
            Helper.getBottom(this.fetchPhotos);
        },
    },
    async mounted() {
        Helper.fillHeight(this.fetchPhotos);
        window.addEventListener("scroll", this.getBottomWraper);
    },
    destroyed() {
        window.removeEventListener("scroll", this.getBottomWraper);
    },
};
</script>

<style scoped>
.grid {
    display: grid;
    grid-template-columns: repeat(
        auto-fit,
        minmax(calc(300px - 4rem - 6px), 1fr)
    );
    grid-gap: 1px;
}
.loading {
    height: 5vh;
    display: flex;
    justify-content: center;
    align-items: flex-end;
}
</style>
