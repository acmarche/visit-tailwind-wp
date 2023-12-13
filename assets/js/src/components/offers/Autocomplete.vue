<script setup>
import {ref} from 'vue'
import {fetchOffersByNameOrCode} from '@/service/offer-service.js'

const offers = ref([])
const searchText = ref('')
const emit = defineEmits(['update-post'])

async function fetchByName() {
  let response = await fetchOffersByNameOrCode(searchText.value)
  offers.value = [...response.data]
}

function onChange() {
  fetchByName()
}

function setResult(selectedOffre) {
  searchText.value = selectedOffre.name
  offers.value = []
  emit('update-post', selectedOffre)
}

</script>
<template>

  <input type="search" name="offre" v-model="searchText"
         @input="onChange"
         class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">

  <ul class="divide-y divide-gray-200 overflow-hidden">
    <li
        v-for="offer in offers"
        :key="offer.codeCgt"
        :value="offer"
        @click="setResult(offer)"
        style="cursor: pointer;"
        class="hover:bg-gray-50 px-2 py-2 text-green-700">
      {{ offer.name }} <span class="text-gray-400">({{ offer.codeCgt }})</span>
    </li>
  </ul>

</template>
