<script setup>
import {ref} from 'vue'
import {addFiltreRequest} from '@/admin/service/filtre-service'
import Autocomplete from "@/admin/components/Autocomplete.vue";

let selectedTypeOffreId = 0
let withChildren = false
const answer = ref(null)
const emit = defineEmits(['refresh-filtres'])
const props = defineProps({
  categoryId: Number
})

async function addFiltre() {
  if (selectedTypeOffreId > 0) {
    try {
      await addFiltreRequest(props.categoryId, selectedTypeOffreId, withChildren)
      emit('refresh-filtres')
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

function onUpdateSelectedTypeOffre(typeOffre) {
  console.log('update'+ typeOffre.id)
  selectedTypeOffreId = typeOffre.id
}
</script>

<template>
  <div class="bg-white2 px-6 pt-10 pb-8 shadow-xl ring-1 ring-gray-900/5">
    <table>
      <tr>
        <td>
          <Autocomplete @update-post="onUpdateSelectedTypeOffre"/>
        </td>
        <td>
          <div class="ml-6 w-60">
            <label for="children" class="text-sm font-medium text-gray-700 mr-2">Avec les enfants</label>
            <input type="checkbox" name="children" id="children" v-model="withChildren"/>
          </div>
        </td>
      </tr>
    </table>
    <button @click="addFiltre()"
            name="add"
            type="button"
            id="addReference"
            class="flex ml-auto mt-3 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
      Ajouter
    </button>
  </div>
</template>