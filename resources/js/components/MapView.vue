<script setup>

    import { ref, onMounted } from "vue";
    import L, { latLng, polyline, rectangle } from "leaflet";
    import "leaflet/dist/leaflet.css";
    import 'leaflet-draw';
    import 'leaflet-draw/dist/leaflet.draw.css';

    // Ref zemelapiui ir poligonams
    const map = ref(null);
    const polygons = ref([]);
    const drawnArea = ref ([]);
    const drawnCoords = ref([]);
    const typedPolygon = ref({
        name: '',
        coordinates: '',
        color: '',
    });

    // Paima polygonus is Laravel API
    const fetchPolygons = async () => {
        try 
        {
            const response = await fetch("http://127.0.0.1:8000/api/polygons");

            polygons.value = data;
            
            drawPolygons();
        }

        catch(error)
        {
            console.error("Problema fetching polygons: ", error);
        }
    };


    // Uzkrauna pati zemelapi
    const loadMap = () => {
        map.value = L.map("map").setView([55.93393, 23.31768], 13); // Default rodoma lokacija, Siauliai

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { // Keisti cia norint kitokio tile layer
            attribution: "&copy; OpenStreetMap contributors",
        }).addTo(map.value);
    };

    // Uzkrauna leaflet-draw 
    const loadDraw = () => {
        //console.log("Kraunamas draw pluginas");

        if (!map.value) return; // Patikrina ar pakrautas map

        drawnArea.value = new L.FeatureGroup(); // Layer group poligonu laikimui
        map.value.addLayer(drawnArea.value);

        // 'Draw' kontrole
        const drawControl = new L.Control.Draw({
            edit:
            {
                featureGroup: drawnArea.value,
                //edit: true,
                //remove: true
            },

            draw:
            {
                polygon: true,
                polyline: true,
                rectangle: true,
                marker: true,
                circle: true,
                circlemarker: true
            }
        });

        map.value.addControl(drawControl);

        // Kai sukuriamas poligonas
        map.value.on(L.Draw.Event.CREATED, (e) => {
            const layer = e.layer; // Paima sukurta figura
            drawnArea.value.addLayer(layer); // Ideda i 'feature group'

            drawnCoords.value = layer.getLatLngs()[0].map(latlng => [latlng.lat, latlng.lng]);
            alert("Poligonas sukurtas. Saugojimas: ");
        });
    };

    // Poligonu vizualizacijai
    const drawPolygons = () => {
        if (!map.value) return;

        polygons.value.forEach((polygon) => {
            L.polygon(polygon.coordinates, {
                color: polygon.color || "#0000ff", // Naudos kaip default jei nera spalvos
                fillColor: polygon.color || "#0000ff",
                fillOpacity: 0.5,
            }).addTo(map.value);
        });
    };

    // Paleidžia kai viskas uzloadinta
    onMounted(() => {
        loadMap();
        fetchPolygons();
        loadDraw();
    });

    // Saugo nupiestus poligonus i DB
    const saveDrawnPolygon = async () => {
        if (!drawnCoords.value.length) return alert("Nera poligono");

        const name = prompt("Iveskite poligono pavadinima: ");
        const color = prompt("Iveskite spalva (hex koda): ");

        const body = {
            name: name || "Be Pavadinimo",
            coordinates: [drawnCoords.value],
            color: color || "#0000ff"
        };

        try
        {
            const res = await fetch("http://127.0.0.1:8000/api/polygons", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(body)
            });

            const data = await res.json();
            console.log("Poligona issaugojo: ", data);
            fetchPolygons(); // Perkraus zemelapi
        }

        catch (err)
        {
            console.error("Nepavyko issaugoti: ", err);
        }
    };

    // Saugo tekstu ivestus poligonus
    const sTypedPolygon = async () => {
        try
        {
            const coords = JSON.parse(typedPolygon.value.coordinates); // Butinai JSON formato turi but

            const body = {
                name: typedPolygon.value.name,
                coordinates: [coords], 
                color: typedPolygon.value.color
            };

            const res = await fetch("http://127.0.0.1:8000/api/polygons", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(body)
            });

            const data = await res.json();
            console.log("Irasytas poligonas: ", data);
            fetchPolygons(); // Perkrauna zemelapi

            // Resetina i tuscias reiksmes
            typedPolygon.value = {
                name: '',
                coordinates: '',
                color: ''
            }
        }

        catch (err)
        {
            alert("Koordinaciu formatas.");
            console.error(err);
        }
    };

    /* map.value.on(L.Draw.Event.EDITED, (e) => {
        const layers = e.layers;

        layers.eachLayer((layer) => {
            const coords = layer.getLatLngs()[0].map(latlng => [latlng.lat, latlng.lng]);

            fetch(`http://127.0.0.1:8000/api/polygons/${layer.polygonId}`, {
                method: "PUT",
                headers: { "Content-Type": "application/json", },
                body: JSON.stringify({
                    coordinates: [coords],
                    color: newColor,
                    name: "Updated Polygon"
                })
            })
            .then(res => res.json())
            .then(data => alert("Poligonas atnaujintas"))
            .catch(err => console.error("Atnaujinimo klaida: ", err));
        });
    }); */

</script>

<template>
    
    <div id="map"></div>

    <button @click="saveDrawnPolygon"> Issaugoti poligona </button>
    <hr />

    <h3> Irasykite poligona ranka </h3>
    <input v-model="typedPolygon.name" placeholder="Pavadinimas" />
    <input type="color" v-model="typedPolygon.color" />
    <textarea v-model="typedPolygon.coordinates" placeholder="[[55.1234, 23.1234], [55.5678, 23.5678]]"></textarea>
    <button @click="sTypedPolygon"> Ikelti Poligona </button>

</template>

<style scoped>

    #map
    {
        height: 100vh;
        width: 100%;
    }

</style>