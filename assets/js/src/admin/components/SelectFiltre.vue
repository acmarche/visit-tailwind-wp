<!-- OBSOLETE -->
<script setup>
import {ref, onMounted} from 'vue'
import {addFiltreRequest, fetchFiltresByParentRequest} from '@admin/service/filtre-service'

const props = defineProps({categoryId: Number})
const rootSelected = ref(null)
const childSelected = ref(null)
const optionsRoot = ref([])
const optionsChild = ref([])
const emit = defineEmits(['refresh-filtres'])

async function fetchChilds() {
  let response = await fetchFiltresByParentRequest(rootSelected.value)
  optionsChild.value = [...response.data]
}

async function addFilter() {
  try {
    await addFiltreRequest(props.categoryId, rootSelected.value, childSelected.value)
    emit('refresh-filtres')
  } catch (e) {
    console.log(e)
  }
  return null
}

onMounted(async () => {
  let response = await fetchFiltresByParentRequest(0)
  optionsRoot.value = [...response.data]
})
</script>
<template>
  <div class="grid columns-2 place-items-center grid-flow-col">
    <select v-model="rootSelected" v-on:change="fetchChilds">
      <option disabled value="">Sélectionnez une catégorie</option>
      <option v-for="option in optionsRoot" :value="option.id">
        {{ option.nom }}
      </option>
    </select>
    <select v-model="childSelected">
      <option disabled value="">Sélectionnez une sous catégorie</option>
      <option v-for="option in optionsChild" :value="option.id">
        {{ option.nom }}
      </option>
    </select>
    <div>
      <br/><br/>
      <button class="button button-primary" type="button" @click="addFilter">
        Ajouter
      </button>
    </div>
  </div>
</template>