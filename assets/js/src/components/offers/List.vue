<script setup>
import {deleteOfferRequest} from '@/service/offer-service.js'

const props = defineProps({offers: Array, categoryId: Number})
const emit = defineEmits(['refresh-offers'])

async function removeOffer(codeCgt) {
  await deleteOfferRequest(props.categoryId, codeCgt)
  emit('refresh-offers')
}
</script>
<template>
  <div v-show="offers.length === 0">
    <p class="mt-3">Aucune offre</p>
  </div>
  <table v-show="offers.length > 0"
         class="mt-4 wp-list-table widefat striped table-view-list toplevel_page_pivot_list">
    <thead>
    <tr>
      <th scope="col" class="manage-column column-booktitle column-primary">Nom</th>
      <th scope="col" class="manage-column column-booktitle column-primary">CodeCgt</th>
      <th scope="col" class="manage-column column-booktitle column-primary">Type</th>
      <th scope="col" class="manage-column column-booktitle column-primary">Voir</th>
      <th scope="col" class="manage-column column-booktitle column-primary">Supprimer</th>
    </tr>
    </thead>
    <tbody>
    <tr v-for="offer in offers">
      <td class="ooktitle column-booktitle has-row-actions column-primary">
        {{ offer.name }}
      </td>
      <td class="ooktitle column-booktitle has-row-actions column-primary">
        <a :href="offer.urlPivot" target="_blank" title="Lien vers Pivot">{{ offer.codeCgt }}</a>
      </td>
      <td class="ooktitle column-booktitle has-row-actions column-primary">
        {{ offer.type }}
      </td>
      <td class="ooktitle column-booktitle has-row-actions column-primary">
        <a :href="offer.urlSite" target="_blank">Voir sur le site</a>
      </td>
      <td>
        <button class="button button-danger" type="button" @click="removeOffer(offer.codeCgt)">
          <span class="dashicons dashicons-trash" style="color: red;"></span> SUPPRIMER
        </button>
      </td>
    </tr>
    </tbody>
  </table>
</template>