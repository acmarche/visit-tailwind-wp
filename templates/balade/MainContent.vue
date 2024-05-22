<script setup>
const filtersSelected = ref({localite: null, type: 11})
const offerSelected = ref(null)
const codeCgt = ref(null)
const previewOpen = ref(false)
const {
  pending,
  refresh,
  data,
  error
} = walksGet()

const {
  pending: pendingFilters,
  data: filters,
  error: errorFilters
} = walkFiltersGet()

const propos = defineProps({
  coords: {
    type: Object,
    required: false
  },
  menuSelected: {
    type: String,
    required: true
  }
})

const menuOpen = defineModel('menuOpen')

watch(codeCgt, (codeCgtValue) => {
  console.log(codeCgtValue)
  if (codeCgtValue) {
    const t = data.value.filter((item) => {
      return codeCgtValue === item.codeCgt;
    })
    if (t.length > 0) {
      offerSelected.value = t[0]
    }
  }
})
watch(offerSelected, (newVale) => {
  console.log(newVale)
})
watch(() => propos.coords, (newValue, oldValue) => {
  if (newValue.accuracy > 0) {
    const walksSave = {localite: walks.value.localite, tags: walks.value.tags}
    walksSave.coordinates = {latitude: newValue.latitude, longitude: newValue.longitude}
    walks.value = walksSave
  }
})
watch(filtersSelected.value, (newValue) => {
  const type = newValue.type
  const locality = newValue.localite
  if (type !== 11) {
    data.value = data.value.filter((item) => {
      return type === item.type;
    })
  }
  if (locality) {
    data.value = data.value.filter((item) => {
      return locality === item.localite;
    })
  }
  if (!locality && type === 11) {
    refresh()
  }
})
</script>
<template>
  <WidgetsError :error="error.message" v-if="error"/>
  <main @esca="menuOpen = false" v-if="data">
    <VisitWalkFilterMobile v-model:filters-selected="filtersSelected" :filters="filters" v-model:menu-open="menuOpen"
                           :data="data"/>
    <VisitWalkPreview v-model:preview-open="previewOpen" :offer-selected="offerSelected"
                      :key="codeCgt"/>
    <div class="mx-auto max-w-full px-0 py-8 sm:px-6 sm:py-12 lg:px-8">
      <div class="border-b border-gray-200 pb-6 px-4 sm:px-0">
        <h1 class="lobster-two-bold
        mx-auto max-w-max
        bg-slate-400
        bg-gradient-to-r from-carto-pink via-carto-main to-carto-gray200
        bg-clip-text text-transparent
        bg-[length:250px_100%] bg-no-repeat
        px-10 py-1 text-center text-4xl font-bold
        animate-shimmer">
          Réseau balades Marche-en-Famenne
        </h1>
        <h1 class="text-4xl font-bold lobster-two-bold tracking-tight text-carto-pink">Carte dynamique</h1>
        <p class="mt-4 text-2xl text-carto-main lobster-two-regular-italic">
          Vous trouverez sur cette carte les balades à pied, à vélo au sein de la commune de Marche-en-Famenne + mettre
          Réseau balades Marche-en-Famenne.
        </p>
      </div>
      <div class="pt-8 grid grid-cols-1 lg:gap-x-8 lg:grid-cols-[auto_minmax(0,1fr)]"
           v-if="data && (menuSelected==='map' || menuSelected==='list')">
        <VisitWalkFilterXl v-model:filters-selected="filtersSelected" :filters="filters" v-model:menu-open="menuOpen"
                           :data/>
        <div class="mt-6 lg:mt-0">
          <h2 class="text-xl lg:text-3xl text-carto-pink py-3 px-3" id="count-result">
            {{ data.length }} balades trouvées
          </h2>
          <div v-show="menuSelected === 'list'">
            <VisitList :data/>
          </div>
          <VisitMapWalk :data v-model:preview-open="previewOpen" :menu-selected="menuSelected"
                        v-model:code-cgt="codeCgt"/>
        </div>
      </div>
      <div class="" v-if="menuSelected === 'about'">
        ABOUT
      </div>
    </div>
  </main>
</template>