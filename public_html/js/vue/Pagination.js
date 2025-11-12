export default {
    props: {
        currentPage: Number,
        maxPage: Number,
    },
    emits: ['update:currentPage'],
    template: `
        <nav class="user-select-none text-center fw-bold" aria-label="Page navigation">
            <ul class="pagination" style="--bs-pagination-font-size: 0.9rem">
                <li :class="{ 'page-item': true, 'disabled': currentPage === 1 }">
                    <a @click.prevent="$emit('update:currentPage', currentPage - 1)" class="page-link" href="#" aria-label="Prev">
                        <span aria-hidden="true">◄</span>
                    </a>
                </li>
                <li v-if="currentPage - 1 > 0" class="page-item">
                    <a @click.prevent="$emit('update:currentPage', currentPage - 1)" class="page-link" href="#">
                        {{ currentPage - 1 }}
                    </a>
                </li>
                <li class="page-item active">
                    <a @click.prevent class="page-link" href="#">
                        {{ currentPage }}
                    </a>
                </li>
                <li v-if="currentPage + 1 < maxPage" class="page-item">
                    <a @click.prevent="$emit('update:currentPage', currentPage + 1)" class="page-link" href="#">
                        {{ currentPage + 1 }}
                    </a>
                </li>
                <li v-if="currentPage + 2 < maxPage && currentPage === 1" class="page-item">
                    <a @click.prevent="$emit('update:currentPage', currentPage + 2)" class="page-link" href="#">
                        {{ currentPage + 2 }}
                    </a>
                </li>
                <li v-if="currentPage + 2 < maxPage" class="page-item">
                    <a @click.prevent class="page-link" href="#">
                        ...
                    </a>
                </li>
                <li v-if="currentPage !== maxPage" class="page-item">
                    <a @click.prevent="$emit('update:currentPage', maxPage)" class="page-link" href="#">
                        {{ maxPage }}
                    </a>
                </li>
                <li :class="{ 'page-item': true, 'disabled': currentPage === maxPage }">
                    <a @click.prevent="$emit('update:currentPage', currentPage + 1)" class="page-link" href="#" aria-label="Next">
                        <span aria-hidden="true">►</span>
                    </a>
                </li>
            </ul>
        </nav>
   `,
}
