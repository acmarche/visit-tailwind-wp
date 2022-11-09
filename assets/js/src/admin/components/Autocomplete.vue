<script setup>
import {ref} from 'vue'
import {fetchFiltresByName} from '@/admin/service/filtre-service'

const typesOffre = ref([])
const searchText = ref('')
const emit = defineEmits(['update-post'])

async function fetchByName() {
  let response = await fetchFiltresByName(searchText.value)
  typesOffre.value = [...response.data]
}

function onChange() {
  fetchByName()
}

function setResult(selectedTypeOffre) {
  searchText.value = selectedTypeOffre.nom
  typesOffre.value = []
  emit('update-post', selectedTypeOffre)
}

</script>
<template>

  <input type="search" name="typeOffre" v-model="searchText"
         @input="onChange"
         class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">

  <ul class="divide-y divide-gray-200 overflow-hidden">
    <li
        v-for="typeOffre in typesOffre"
        :key="typeOffre.id"
        :value="typeOffre"
        @click="setResult(typeOffre)"
        style="cursor: pointer;"
        class="hover:bg-gray-50 px-2 py-2 text-green-700">
      {{ typeOffre.nom }} <span class="text-gray-400">({{ typeOffre.urn }})</span>
    </li>
  </ul>

</template>
