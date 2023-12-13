<script setup>
import {ref, onMounted} from 'vue'
import {fetchFiltresByCategoryRequest} from '@/service/filtre-service'
import Add from "@/components/filters/Add.vue";
import List from "@/components/filters/List.vue";

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
    <Add :categoryId="categoryId" @refresh-filtres="refreshFiltres"/>
    <List :categoryId="categoryId" :filtres="filtres" @refresh-filtres="refreshFiltres"/>
  </main>

</template>