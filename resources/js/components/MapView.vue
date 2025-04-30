<script setup>

    import { ref, onMounted } from "vue";
    import L, { latLng, polyline, rectangle } from "leaflet";
    import 'leaflet-draw';
    import * as turf from '@turf/turf';
    import 'leaflet-geometryutil';
    import CropPlanner from './CropPlanner.vue';
    import { method, values } from "lodash";
    import 'leaflet/dist/leaflet.css';
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
    const selectedPolygon = ref(null); // Pasirinktas poligonas
    const editForm = ref({
        id: null,
        name: '',
        color: '',
        coordinates: ''
    });
    const showEditPopup = ref(false); // Popup rodymas, default - nerodo
    const statusColors = {
        'Užimtas': '#d06060', // Raudona
        'Laisvas': '#85d060', // Zalia
        'Dirbamas': '#f7f12f', // Geltona
        'Rezervuotas': '#609bd0', // Melyna
    }
    const statusMapping = {
        'Užimtas' : 1,
        'Laisvas' : 2,
        'Dirbamas' : 3,
        'Rezervuotas' : 4
    };

    // Paima polygonus is Laravel API
    const fetchPolygons = async () => {
        try 
        {
            const response = await fetch("http://127.0.0.1:8000/api/polygons");

            const data = await response.json();
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

        const osm = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors"
        });

        const esri = L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", {
            attribution: "Tiles © Esri"
        });

        const cartoLight = L.tileLayer("https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png", {
            attribution: '&copy; <a href="https://carto.com/">Carto</a>',
        });

        osm.addTo(map.value);

        const baseLayers = {
            "OpenStreetMap": osm,
            "ESRI Satellite": esri,
            "Carto Light": cartoLight
        };

        L.control.layers(baseLayers).addTo(map.value);  

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
                remove: true,
                selectedPathOptions: { maintainColor: true }
            },

            draw:
            {
                polygon: {
                    allowIntersection: false,
                    drawError: {
                        color: '#b00b00',
                        message: 'Error: Netinkamas poligonas'
                    },
                    shapeOptions: {
                        color: '#bada55'
                    }
                },
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

            if(!layer.getLatLngs || typeof layer.getLatLngs !== 'function')
            {
                alert("Sukurtas objektas nėra poligonas");
                return;
            }

            const latLngs = layer.getLatLngs();

            if (!latLngs?.[0]?.length)
            {
                alert("Netinkamos koordinatės");
                return;
            }

            drawnArea.value.addLayer(layer); // Ideda i 'feature group'
            drawnCoords.value = layer.getLatLngs()[0].map(latlng => [latlng.lat, latlng.lng]);

            const geojson = layer.toGeoJSON();
            const areaSqMeters = turf.area(geojson);
            const areaHectares = (areaSqMeters / 10000).toFixed(2);

            layer.bindTooltip(`${areaHectares} ha`, {
                permanent: true,
                direction: 'center',
                className: 'area-label'
            }).openTooltip();

            alert("Poligonas sukurtas. Saugojimas: ");

            typedPolygon.value.area = parseFloat(areaHectares);
        });

        map.value.on(L.Draw.Event.EDITED, (e) => {
            const layers = e.layers;

            layers.eachLayer((layer) => {
                const coords = layer.getLatLngs()[0].map(latlng => [latlng.lat, latlng.lng]);

                const newColor = prompt("Iveskite nauja spalva (hex): ", "00ff00");

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
        });

        map.value.on(L.Draw.Event.EDITSTART, () => {
            map.value.doubleClickZoom.disable();
        });

        map.value.on(L.Draw.Event.EDITSTOP, () => {
            map.value.doubleClickZoom.enable();
        });

        /*map.value.on(L.Draw.Event.DRAWVERTEX, function (e) {
            const latlngs = e.layers._layers;
            const coordinates = Object.values(latlngs).map(l => [l._latlng.lng, l._latlng.lat]);

            if(coordinates.length > 2)
            {
                const poly = turf.polygon([[...coordinates, coordinates[0]]]);
                const area = turf.area(poly);

                const hectares = area / 10000;
                console.log('Plotas gyvai: ', hectares.toFixed(2) + ' ha');
            }
        })*/
    };

    // Poligonu vizualizacijai
    /*const drawPolygons = () => {
        if (!map.value) return;

        polygons.value.forEach((polygon) => {
            L.polygon(polygon.coordinates, {
                color: polygon.color || "#0000ff", // Naudos kaip default jei nera spalvos
                fillColor: polygon.color || "#0000ff",
                fillOpacity: 0.5,
            }).addTo(map.value);
        });
    };*/

    const drawPolygons = () => {
        if (!map.value) return;

        drawnArea.value.clearLayers();

        polygons.value.forEach((polygon) => {
            console.log("Pilnas poligono objektas:", polygon);

            const status =
                typeof polygon.statusas === 'object'
                ? polygon.statusas?.statusas
                : polygon.statusas;
            
            const polyColor = polygon.color || statusColors[status] || '#cccccc';

            const poly = L.polygon(polygon.coordinates, {
                color: polyColor,
                fillColor: polyColor,
                fillOpacity: 0.5,
            });

            poly.polygonId = polygon.id;

            //poly.on("Click", () => openEditPopup(polygon, poly));
            /*poly.on("click", () => {
                console.log("poligonas paspaustas");
                openEditPopup(polygon, poly);
            });*/

            poly.on("click", (e) => {
                L.DomEvent.stopPropagation(e); // Saugo kad event neissiupustu
                console.log("Poligonas paspaustas: ", polygon.id);
                openEditPopup(polygon, poly);
            });

            poly.on("contextmenu", (e) => {
                L.DomEvent.stopPropagation(e);
                selectedPolygon.value = polygon;
                console.log("Right-Click ant poligono: ", poly.polygonId);
            });

            drawnArea.value.addLayer(poly);
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

        if(!drawnCoords.value.length) return alert("Nėra poligono.");

        const name = prompt("Įveskite poligono pavadinimą:");
        const input = prompt("Įveskite spalvą (hex kodą) arba statusą (Užimtas, Laisvas, Dirbamas, Rezervuotas):");
    
        const reversedCoords = drawnCoords.value.map(([lat, lng]) => [lng, lat]);
        const geojson = {
            type: "Polygon",
            coordinates: [reversedCoords]
        };

        const area = turf.area(geojson) / 10000; // Verčia į hektarus
        const roundedArea = Math.round(area * 100) / 100;
        typedPolygon.value.area = roundedArea; // Tikslesnės kad įvestu teisingai į DB

        const body = {
            name: name || "Be pavadinimo",
            coordinates: [drawnCoords.value],
            plotas: roundedArea,
        };

        // Tikrins ar input spalva ar statusas
        if(statusMapping[input])
        {
            body.statusas_id = statusMapping[input];
        }

        else if(/^#([0-9A-F]{3}){1,2}$/i.test(input))
        {
            body.color = input;
        }

        else
        {
            alert("Netinkama įvestis - turi būti statusas arba spalva HEX formato");
            return;
        }

        try
        {
            const res = await fetch("http://127.0.0.1:8000/api/polygons", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(body)
            });

            if(!res.ok) throw new Error(await res.text());

            const data = await res.json();
            console.log("Poligonas išsaugotas: ", data);
            fetchPolygons(); // Perkrauna kad mestu poligona
        }

        catch(err)
        {
            console.error("Klaida saveDrawPolygon: ", err);
            alert("Nepavyko išsaugoti: " + err.message);
        }
    };


        /*const name = prompt("Iveskite poligono pavadinima: ");
        const color = prompt("Iveskite spalva (hex koda): ");

        const body = {
            name: name || "Be Pavadinimo",
            coordinates: [drawnCoords.value],
            color: color || "#0000ff",
            area: parseFloat(areaHectares)
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
    };*/

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

    // Poligono atnaujinimo popup atidarymui
    const openEditPopup = (polygon, layer) => {
        selectedPolygon.value = layer;

        editForm.value = {
            id: polygon.id,
            name: polygon.name,
            color: polygon.color || '#0000ff',
            coordinates: polygon.coordinates // Tik rodymui
        };

        showEditPopup.value = true; // Turetu atidaryti popup
    };

    // Poligono atnaujinimo pateikimui
    const submitEdit = async () => {
        /*try
        {
            const coordinates = typeof editForm.value.coordinates === 'string'
            ? JSON.parse(editForm.value.coordinates)
            : editForm.value.coordinates;
        
            const res = await fetch(`http://127.0.0.1:8000/api/polygons/${editForm.value.id}`, {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    name: editForm.value.name,
                    color: editForm.value.color,
                    coordinates: coordinates // Panaudoja parsed kordinates
                })
            });

            const data = await res.json();
            alert("Atnaujinta");
            showEditPopup.value = false; // Vel paslepia popup
            fetchPolygons(); // Perkauna site'a
        }

        catch (err)
        {
            console.error("Klaida atnaujinime: ", err);
        }*/

        try
        {
            let coordinates = editForm.value.coordinates;

            if (typeof coordinates === 'string')
            {
                const parsed = tryParseJSON(coordinates);

                if (!Array.isArray(parsed))
                {
                    alert("Netinkamas koordinaciu formatas");
                    return;
                }

                coordinates = parsed;
            }

            const res = await fetch(`http://127.0.0.1:8000/api/polygons/${editForm.value.id}`, {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    name: editForm.value.name,
                    color: editForm.value.color,
                    coordinates: Array.isArray(editForm.value.coordinates)
                        ? editForm.value.coordinates
                        : tryParseJSON(editForm.value.coordinates)
                })
            });

            const data = await res.json();
            alert("Atnaujinta");
            showEditPopup.value = false;

            fetchPolygons();
        }

        catch (err)
        {
            console.error("Klaida atnaujinime: ", err);
        }

    };

    const tryParseJSON = (str) => {
        try 
        {
            return JSON.parse(str);
        }

        catch 
        {
            return null; // safer than returning raw string
        }
    };

</script>

<template>

    <CropPlanner :selected-polygon="selectedPolygon" />
    
    <div id="map"></div>

    <button @click="saveDrawnPolygon"> Issaugoti poligona </button>
    <hr />

    <h3> Irasykite poligona ranka </h3>
    
    <input v-model="typedPolygon.name" placeholder="Pavadinimas" />
    <input type="color" v-model="typedPolygon.color" />
    <textarea v-model="typedPolygon.coordinates" placeholder="[[55.1234, 23.1234], [55.5678, 23.5678]]"></textarea>
    <button @click="sTypedPolygon"> Ikelti Poligona </button>

    <div v-if="showEditPopup" class="popup">
        <h3> Redaguoti poligona </h3>

        <input v-model="editForm.name" placeholder="Pavadinimas" />
        <input type="color" v-model="editForm.color" />
        <!--<textarea v-model="editForm.coordinates" rows="5" cols="30" readonly></textarea>-->
        <textarea
            :value="JSON.stringify(editForm.coordinates, null, 2)"
            @input="editForm.coordinates = tryParseJSON($event.target.value)"
            rows="5"
            cols="30"
        ></textarea>
        
        <br />

        <button @click="submitEdit"> Išsaugoti </button>
        <button @click="showEditPopup = false"> Atšaukti </button>
    </div>

</template>

<style scoped>

    #map
    {
        height: 100vh;
        width: 100%;
    }

    .popup
    {
        position: absolute;
        top: 20px;
        left: 20px;
        background-color: #f1f1f1;
        padding: 15px;
        border: 2px solid #ccc;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        z-index: 10001;
        width: 300px;
    }

    .area-label
    {
        background-color: rgba(255, 255, 255, 0.8);
        padding: 2px 4px;
        border-radius: 4px;
        font-weight: bold;
    }

    .crop-planner
    {
        z-index: 10002; /* Auksciau popup!!! */
    }

    .leaflet-draw-tooltip 
    {
        pointer-events: none;
    }

</style>