<script setup>

    import { ref, computed } from 'vue';
    import { watch } from 'vue';

    const props = defineProps({
        selectedPolygon: Object
    });

    const paseliai = ref([
        { id: 1, pavadinimas: 'Kvieciai', rotacijos_grupe: 1 },
        { id: 2, pavadinimas: 'Kukuruzai', rotacijos_grupe: 2 },
        { id: 3, pavadinimas: 'Pupos', rotacijos_grupe: 3 },
        { id: 4, pavadinimas: 'Bulves', rotacijos_grupe: 4 },
    ]);

    const selectedCrop = ref(null);
    const rotationError = ref('');

    const cropHistory = computed(() => {
        return props.selectedPolygon?.paseliu_istorija || [];
    });

    const availableCrops = computed(() => {
        // Filtruojami paseliai pagal rotacijos taisykles
        const lastCropGroup = cropHistory.value[0]?.rotacijos_grupe;
        
        return paseliai.value.filter(paselis =>
            !lastCropGroup ||
            (paselis.rotacijos_grupe !== lastCropGroup && paselis.rotacijos_grupe !== (lastCropGroup % 4) + 1)
        );
    });

    function validateRotation()
    {
        if(!selectedCrop.value) return;

        const lastCrop = cropHistory.value[0];
        const currentCrop = paseliai.value.find(p => p.id === selectedCrop.value);

        if(lastCrop && lastCrop.rotacijos_grupe === currentCrop.rotacijos_grupe)
        {
            rotationError.value = "Negalima sodinti tos pacios grupes paseliu is eiles";
        }

        else
        {
            rotationError.value = '';
        }
    }

    async function assignCrop() 
    {
        const newCrop = paseliai.value.find(p => p.id === selectedCrop.value);
        
        try
        {
            const response = await fetch (`/api/polygons/${props.selectedPolygon.id}/paseliai`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    paselio_id: newCrop.id,
                    metai: new Date().getFullYear()
                })
            });

            if(!response.ok)
            {
                const error = await response.json();
                throw new Error(error.message || 'Nepavyko priskirti paselio');
            }

            const data = await response.json()

            Object.assign(props.selectedPolygon, data.polygon);

            // Atnaujinamas su backend
            /*props.selectedPolygon.paseliu_istorija = data.polygon.paseliu_istorija;
            props.selectedPolygon.paselio_id = data.polygon.paselio_id;*/
        }

        catch(err)
        {
            console.error('Klaida priskiriant paseli: ', err);
            alert('Nepavyko priskirti paselio: ' + err.message);
        }
    }

    function getCropName(paselioId)
    {
        return paseliai.value.find(p => p.id === paselioId)?.pavadinimas || 'Nezinomas';
    }

    watch(() => props.selectedPolygon, (newVal) => {
        if(newVal)
        {
            selectedCrop.value = newVal.paselio_id || null;
        }
    }, { immediate: true }); // immediate: true padaro kad iskart veiktu ant paleidimo

</script>

<template>
    <div class="crop-planner">
        <h3> Paseliu Rotacijos Planavimas </h3>

        <div v-if="selectedPolygon">
            <h4> {{ selectedPolygon.name }} </h4>

            <div class="crop-history">
                <div v-for="(entry, index) in cropHistory" :key="index">
                    {{ entry.metai }}: {{ entry.pavadinimas }}
                </div>
            </div>

            <select v-model="selectedCrop" @change="validateRotation">
                <option :value="null" disabled> Pasirinkite paseli... </option>
                <option v-for="paselis in availableCrops" :value="paselis.id" :key="paselis.id">
                    {{ paselis.pavadinimas }} (Grupė {{ paselis.rotacijos_grupe }})
                </option>
            </select>

            <button @click="assignCrop" :disabled="!selectedCrop">
                Pasirinkti Paseli
            </button>

            <div v-if="rotationError" class="error">
                {{ rotationError }}
            </div>
        </div>

        <div v-else>
            Pasirinkite lauka planavimui
        </div>
    </div>
</template>

<style scoped>

    .crop-planner
    {
        position: absolute;
        right: 20px;
        top: 20px;
        background: white;
        padding: 15px;
        z-index: 1000;
        width: 300px;
    }

    .error
    {
        color: red;
        margin-top: 10px;
    }

    .crop-history
    {
        margin: 10px 0;
        padding: 10px;
        background: #f1f1f1;
    }

</style>