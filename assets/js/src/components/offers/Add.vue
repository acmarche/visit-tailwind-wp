<script setup>
import {ref} from 'vue'
import {addOfferRequest} from '@/service/offer-service.js'
import Autocomplete from "@/components/offers/Autocomplete.vue";

let selectedCodeCgt = null
const answer = ref(null)
const emit = defineEmits(['refresh-offers'])
const props = defineProps({
  categoryId: Number
})

async function addOffer() {
  if (selectedCodeCgt != null) {
    try {
      await addOfferRequest(props.categoryId, selectedCodeCgt)
      emit('refresh-offers')
      answer.value = 'oki'
    } catch (error) {
      answer.value = 'Error! Could not reach the API. ' + error
      console.log(error)
    }
    return null
  } else {
    console.log('Pas de selection')
  }
}

function onUpdateSelectedOffer(offer) {
  console.log('update'+ offer.codeCgt)
  selectedCodeCgt = offer.codeCgt
}
</script>

<template>
  <div class="bg-white2 px-6 pt-10 pb-8 shadow-xl ring-1 ring-gray-900/5">
    <table>
      <tr>
        <td>
          <Autocomplete @update-post="onUpdateSelectedOffer"/>
        </td>
      </tr>
    </table>
    <button @click="addOffer()"
            name="add"
            type="button"
            id="addReference"
            class="flex ml-auto mt-3 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
      Ajouter
    </button>
  </div>
</template>