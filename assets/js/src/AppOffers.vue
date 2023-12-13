<script setup>
import {ref, onMounted} from 'vue'
import {fetchOffersByCategoryRequest} from '@/service/offer-service'
import Add from "@/components/offers/Add.vue";
import List from "@/components/offers/List.vue";

const offers = ref([])
const categoryId = ref(0)

async function refreshOffers() {
  if (categoryId.value > 0) {
    let response = await fetchOffersByCategoryRequest('', categoryId.value)
    offers.value = [...response.data]
  }
}

onMounted(async () => {
  categoryId.value = Number(document.getElementById('offers-box').getAttribute('data-category-id'));
  await refreshOffers()
})
</script>

<template>
  <header>
    <h1 class="text-3xl font-bold text-yellow-400">
      Nom ou code de l'offre
    </h1>
  </header>

  <main class="wrapper">
    <Add :categoryId="categoryId" @refresh-offers="refreshOffers"/>
    <List :categoryId="categoryId" :offers="offers" @refresh-offers="refreshOffers"/>
  </main>

</template>