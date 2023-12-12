<script setup>
import {ref, onMounted} from 'vue'
import {fetchFiltresByCategoryRequest} from '@/admin/service/filtre-service'
import AddFilter from "@/admin/components/AddFilter.vue";
import ListFilter from "@/admin/components/ListFilter.vue";

const filtres = ref([])
const categoryId = ref(0)

async function refreshFiltres() {
  if (categoryId.value > 0) {
    let response = await fetchFiltresByCategoryRequest('', categoryId.value, 1, 0)
    filtres.value = [...response.data]
  }
}

onMounted(async () => {
  categoryId.value = Number(document.getElementById('filters-box').getAttribute('data-category-id'));
  await refreshFiltres()
})
</script>

<template>
  <header>
    <h1 class="text-3xl font-bold text-yellow-400">
      Hello world!
    </h1>
  </header>

  <main class="wrapper">
    <AddFilter :categoryId="categoryId" @refresh-filtres="refreshFiltres"/>
    <ListFilter :categoryId="categoryId" :filtres="filtres" @refresh-filtres="refreshFiltres"/>
  </main>

</template>