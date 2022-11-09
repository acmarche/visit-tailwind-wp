<script setup>
import {ref, onMounted} from 'vue'
import {fetchFiltresByCategoryRequest} from '@/admin/service/filtre-service'
import AddFilter from "@/admin/components/AddFilter.vue";
import ListFiltre from "@/admin/components/ListFiltre.vue";

const filtres = ref([])
const categoryId = ref(0)

async function refreshFiltres() {
  if (categoryId.value > 0) {
    let response = await fetchFiltresByCategoryRequest('', categoryId.value,1,0)
    filtres.value = [...response.data]
  }
}

onMounted(async () => {
  categoryId.value = Number(document.getElementById('filtres-box').getAttribute('data-category-id'));
  await refreshFiltres()
})
</script>

<template>
  <AddFilter :categoryId="categoryId" @refresh-filtres="refreshFiltres"/>
  <ListFiltre :categoryId="categoryId" :filtres="filtres" @refresh-filtres="refreshFiltres"/>
</template>