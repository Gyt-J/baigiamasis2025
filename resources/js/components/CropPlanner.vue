<script setup>

    import { ref, computed, watch, onMounted } from 'vue';
    import PlotWeather from './PlotWeather.vue';

    const props = defineProps({
        selectedPolygon: Object
    });

    /*const paseliai = ref([
        { id: 1, pavadinimas: 'Kvieciai', rotacijos_grupe: 1 },
        { id: 2, pavadinimas: 'Kukuruzai', rotacijos_grupe: 2 },
        { id: 3, pavadinimas: 'Pupos', rotacijos_grupe: 3 },
        { id: 4, pavadinimas: 'Bulves', rotacijos_grupe: 4 },
    ]);*/

    const paseliai = ref([]); // new
    const selectedCrop = ref(null); // buvo
    const rotationError = ref(''); // buvo
    const newCropName = ref(''); // new
    const newCropGroup = ref(1); // new
    const showAddCropForm = ref(false); // new

    onMounted(async () => { // new
        await fetchCrops();
    });

    async function fetchCrops() { // new
        try
        {
            const response = await fetch ('api/paseliai');
            paseliai.value = await response.json();
        }

        catch(err)
        {
            console.error('Nepavyko gauti paseliu:', err);
        }
    }

    const cropHistory = computed(() => { // buvo
        return props.selectedPolygon?.paseliu_istorija || [];
    });

    const availableCrops = computed(() => { // buvo
        // Filtruojami paseliai pagal rotacijos taisykles
        const lastCropGroup = cropHistory.value[0]?.rotacijos_grupe;
        
        return paseliai.value.filter(paselis =>
            !lastCropGroup ||
            (paselis.rotacijos_grupe !== lastCropGroup && paselis.rotacijos_grupe !== (lastCropGroup % 4) + 1)
        );
    });

    function validateRotation() // buvo
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

    async function addNewCrop() { // new
        if(!newCropName.value.trim()) return;

        try
        {
            // tikrina ar nesidubliuoja
            const normalizedNewName = newCropName.value.trim().toLowerCase();
            const exists = paseliai.value.some(paselis => paselis.pavadinimas.toLowerCase() === normalizedNewName);

            if(exists)
            {
                alert('Toks paselis jau egzistuoja');
                return;
            }

            const response = await fetch('/api/paseliai', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pavadinimas: newCropName.value.trim(),
                    rotacijos_grupe: newCropGroup.value
                })
            });

            if(!response.ok)
            {
                throw new Error('Nepavyko prideti paselio');
            }

            const newCrop = await response.json();
            paseliai.value.push(newCrop);

            // Perkaunama forma
            newCropName.value = '';
            newCropGroup.value = 1;
            showAddCropForm.value = false;
        }

        catch(err)
        {
            console.error('Klaida pridedant paseli:', err);
            alert('Klaida: ' + err.message);
        }
    }

    async function assignCrop() // buvo
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

    function getCropName(paselioId) // buvo
    {
        return paseliai.value.find(p => p.id === paselioId)?.pavadinimas || 'Nezinomas';
    }

    watch(() => props.selectedPolygon, (newVal) => { // buvo
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

            <!-- <select v-model="selectedCrop" @change="validateRotation">
                <option :value="null" disabled> Pasirinkite paseli... </option>
                <option v-for="paselis in availableCrops" :value="paselis.id" :key="paselis.id">
                    {{ paselis.pavadinimas }} (Grupė {{ paselis.rotacijos_grupe }})
                </option>
            </select> -->

            <div class="crop-selection"> <!-- new -->
                <select v-model="selectedCrop" @change="validateRotation">
                    <option :value="null" disabled> Pasirinkite paseli... </option>
                    <option v-for="paselis in availableCrops" :value="paselis.id" :key="paselis.id">
                        {{ paselis.pavadinimas }} (Grupe {{ paselis.rotacijos_grupe }})
                    </option>
                </select>

                <!-- <button @click="assignCrop" :disabled="!selectedCrop">
                    Pasirinkti Paseli
                </button> -->

                <button @click="showAddCropForm = true" class="add-crop-btn"> <!-- new -->
                    + Prideti nauja paseli
                </button>
            </div>
            
            <div v-if="showAddCropForm" class="add-crop-form"> <!-- new -->
                <h5> Prideti nauja paseli </h5>
                <input v-model="newCropName" placeholder="Paselio pavadinimas" />
                <select v-model.number="newCropGroup">
                    <option value="1"> Žiemos </option>
                    <option value="2"> Pavasario </option>
                    <option value="3"> Vasaros </option>
                    <option value="4"> Rudens </option>
                </select>

                <div class="form-actions">
                    <button @click="addNewCrop" :disabled="!newCropName.trim()"> Prideti </button>
                    <button @click="showAddCropForm = false"> Atsaukti </button>
                </div>
            </div>

            <button @click="assignCrop" :disabled="!selectedCrop" class="assign-btn"> <!-- new -->
                Pasirinkti paseli
            </button>

            <div v-if="rotationError" class="error">
                {{ rotationError }}
            </div>
        </div>

        <div v-else>
            Pasirinkite lauka planavimui
        </div>

        <PlotWeather v-if="selectedPolygon" :polygon="selectedPolygon" />
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

    .crop-selection /* new */
    {
        margin: 15px 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    select /* new */
    {
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }

    button /* new */
    {
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .add-crop-btn /* new */
    {
        background-color: #f1f1f1;
        color: #333;
    }

    .add-crop-btn:hover /* new */
    {
        background-color: #e1e1e1;
    }

    .assign-btn /* new */
    {
        background-color: #4caf50;
        color: white;
        margin-top: 10px;
        width: 100%;
    }

    .assign-btn:disabled /* new */
    {
        background-color: #ccc;
        cursor: not-allowed;
    }

    .add-crop-form /* new */
    {
        margin-top: 15px;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 4px;
    }

    .add-crop-form h5 /* new */
    {
        margin-top: 0;
        margin-bottom: 10px;
    }

    .add-crop-form input /* new */
    {
        width: 100%;
        padding: 8px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .form-actions /* new */
    {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .form-actions button:first-child /* new */
    {
        background-color: #4caf50;
        color: white;
    }

    .form-actions button:last-child /* new */
    {
        background-color: #4caf50;
        color: white;
    }

    .error
    {
        color: red;
        margin-top: 10px;
        padding: 8px;
        background-color: #ffeeee;
        border-radius: 4px;
    }

    .crop-history
    {
        margin: 10px 0;
        padding: 10px;
        background: #f1f1f1;
        border-radius: 4px;
    }

</style>