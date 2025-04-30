<script setup>

    import { ref, watch, onMounted } from 'vue';
    import * as turf from '@turf/turf';

    const props = defineProps({
        polygon: Object, // Pasirinkto ploto poligonas
    });

    const weather = ref(null);
    const loading = ref(false);
    const error = ref(null);

    // Fetch'ina orus poligono centre
    async function fetchWeatherAPI(latitude, longitude) 
    {
        try
        {
            const response = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${latitude}&longitude=${longitude}&daily=temperature_2m_max,precipitation_sum&timezone=auto&forecast_days=3`);

            console.log("API Response with: lat =", latitude, "lng =", longitude); // debugginam

            if(!response.ok) throw new Error("API error");
            const data = await response.json();

            return {
                properties: {
                    periods: data.daily.time.map((time, i) => ({
                        name: new Date(time).toLocaleDateString('lt-LT', { weekday: 'long' }),
                        temperature: data.daily.temperature_2m_max[i],
                        probabilityOfPrecipitation:
                        {
                            value: data.daily.precipitation_sum[i] > 0 ? 50 : 0
                        },
                        shortForecast: data.daily.precipitation_sum[i] > 0 ? "Lietus" : "Sauleta"
                    }))
                }
            };
        }

        catch(err)
        {
            console.error("API Error: ", err);
            return mockWeather;
        }
    }

    const mockWeather = {
        properties: {
            periods: [
                {
                    name: "Siandien",
                    temperature: 22,
                    probabilityOfPrecipitation: { value: 10 },
                    shortForecast: "Sauleta"
                },
                {
                    name: "Rytoj",
                    temperature: 18,
                    probabilityOfPrecipitation: { value: 60 },
                    shortForecast: "Lietus"
                }
            ]
        }
    };

    async function fetchWeather()
    {
        if(!props.polygon?.coordinates) return;

        loading.value = true;

        try
        {
            let polygonCoords = props.polygon.coordinates;

            if(Array.isArray(polygonCoords[0][0][0]))
            {
                polygonCoords = polygonCoords[0];
            }

            const ring = polygonCoords[0];
            if(ring.length && (ring[0][0] !== ring[ring.length -1][0] || ring [0][1] !== ring[ring.length - 1][1]))
            {
                ring.push([...ring[0]]);
            }

            const centroid = turf.centroid(turf.polygon([ring]));
            const [lng, lat] = centroid.geometry.coordinates;
            console.log("Fetching orai: ", lng, lat);

            weather.value = await fetchWeatherAPI(lng, lat);
        }

        catch (err)
        {
            console.error("Oru fetch nepavyko: ", err); 
            error.value = "Oru duomenys negalima (naudojami mock duomenys)";
            weather.value = mockWeather;
        }

        finally
        {
            loading.value = false;
        }
    }

    onMounted(() => {
        if(props.polygon?.coordinates)
        {
            fetchWeather();
        }
    });

    watch(() => props.polygon, (newPolygon) => { // Reikia pridet sita nes Vue 3 is naujo pazymejus poligona, onMount nebesureguoja
        if(newPolygon?.coordinates)
        {
            fetchWeather();
        }
    });

</script>

<template>

    <div class="weather-card">
        <h4> Oru Prognoze </h4>
        <div v-if="loading"> Kraunama... </div>
        <div v-if="error" class="error"> {{ error }} </div>

        <div v-if="weather" class="forecast">
            <div v-for="(day, i) in weather.properties.periods" :key="i" class="day">
                <strong> {{ day.name }} </strong>
                <div> {{ Math.round(day.temperature) }}°C </div>
                <div> {{ day.probabilityOfPrecipitation.value }}% </div>
                <div> {{ day.shortForecast }} </div>
            </div>
        </div>
    </div>

</template>

<style scoped>

    .weather-card
    {
        background: white;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .forecast
    {
        display: flex;
        gap: 10px;
    }

    .day
    {
        padding: 8px;
        border: 1px solid #eee;
    }

    .error
    {
        color: red;
    }

</style>