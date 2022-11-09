<script setup>
import {deleteFiltreRequest} from '@/admin/service/filtre-service'

const props = defineProps({filtres: Array, categoryId: Number})
const emit = defineEmits(['refresh-filtres'])

async function removeFiltre(id) {
  await deleteFiltreRequest(props.categoryId, id)
  emit('refresh-filtres')
}
</script>
<template>
  <div v-show="filtres.length === 0">
    <p class="mt-3">Aucun filtre</p>
  </div>
  <table v-show="filtres.length > 0"
         class="mt-4 wp-list-table widefat striped table-view-list toplevel_page_pivot_list">
    <thead>
    <tr>
      <th scope="col" class="manage-column column-booktitle column-primary">Nom</th>
      <th scope="col" class="manage-column column-booktitle column-primary">Urn</th>
      <th scope="col" class="manage-column column-booktitle column-primary">Enfants</th>
      <th scope="col" class="manage-column column-booktitle column-primary">Supprimer
      </th>
    </tr>
    </thead>
    <tbody>
    <tr v-for="filtre in filtres">
      <td class="ooktitle column-booktitle has-row-actions column-primary">
        {{ filtre.nom }}
      </td>
      <td class="ooktitle column-booktitle has-row-actions column-primary">
        {{ filtre.urn }}
      </td>
      <td class="ooktitle column-booktitle has-row-actions column-primary">
        {{ filtre.withChildren }}
      </td>
      <td>
        <button class="button button-danger" type="button" @click="removeFiltre(filtre.id)">
          <span class="dashicons dashicons-trash"></span> SUPPRIMER
        </button>
      </td>
    </tr>
    </tbody>
  </table>
</template>