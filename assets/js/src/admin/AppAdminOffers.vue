<script setup>
import {ref, onMounted} from 'vue'
import {fetchOffersByNameOrCode} from '@/admin/service/offer-service'
import AddOffer from "@/admin/components/AddOffer.vue";
import ListOffer from "@/admin/components/ListOffer.vue";

const offers = ref([])
const categoryId = ref(0)

async function refreshOffers() {
  if (categoryId.value > 0) {
    let response = await fetchOffersByNameOrCode('', categoryId.value,1,0)
    offers.value = [...response.data]
  }
}

onMounted(async () => {
  categoryId.value = Number(document.getElementById('offers-box').getAttribute('data-category-id'));
  await refreshOffers()
})
</script>

<template>
  <AddOffer :categoryId="categoryId" @refresh-offers="refreshOffers"/>
  <ListOffer :categoryId="categoryId" :offers="offers" @refresh-offers="refreshOffers"/>
</template>