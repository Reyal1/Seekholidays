<?php
$tool = $_GET["tool"] ?? "plan";

function visaData() {
    return [
        "Netherlands" => [
            "Thailand" => ["Visa Free", 30, "Extendable"],
            "USA" => ["ESTA", 90, "Apply online"],
            "India" => ["eVisa", 30, "Online approval"]
        ],
        "UK" => [
            "USA" => ["ESTA", 90, "Required"],
            "Thailand" => ["Visa Free", 30, ""]
        ]
    ];
}

$data = visaData();
$result = null;

// VISA
if ($tool === "visa" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $from = $_POST["from"] ?? "";
    $to = $_POST["to"] ?? "";
    $result = $data[$from][$to] ?? ["Unknown","-","No data"];
}

// SCHENGEN
if ($tool === "schengen" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $used = intval($_POST["used"] ?? 0);
    $remaining = 90 - $used;
    $result = [$used,$remaining,$remaining>=0?"OK":"OVERSTAY"];
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Travel OS v3</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<style>
body{font-family:system-ui;margin:0;background:#f6f8ff}
.wrap{max-width:1100px;margin:auto;background:#fff;padding:20px;border-radius:12px}

.nav{display:flex;gap:10px}
.nav a{flex:1;text-align:center;padding:10px;background:#eee;border-radius:8px;text-decoration:none}
.nav a.active{background:#1a73e8;color:#fff}

input,button{width:100%;padding:10px;margin-top:8px;border-radius:8px;border:1px solid #ddd}
button{background:#1a73e8;color:#fff;border:none;cursor:pointer}

.grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}

#map{height:500px;border-radius:10px;margin-top:10px}

.stop{padding:8px;background:#eee;margin-top:5px;border-radius:6px;cursor:grab}

.card{padding:10px;margin-top:10px;background:#f2f6ff;border-left:5px solid #1a73e8}
</style>
</head>

<body>

<div class="wrap">

<h2>🌍 Travel OS v3</h2>

<div class="nav">
    <a class="<?= $tool=='plan'?'active':'' ?>" href="?tool=plan">Trip</a>
    <a class="<?= $tool=='visa'?'active':'' ?>" href="?tool=visa">Visa</a>
    <a class="<?= $tool=='schengen'?'active':'' ?>" href="?tool=schengen">Schengen</a>
</div>

<!-- ================= TRIP ================= -->
<?php if ($tool=="plan"): ?>

<div class="grid">

<div>
    <h3>🧳 Trip Builder</h3>

    <input id="place" placeholder="City or place">
    <button onclick="addPlace()">Add Stop</button>

    <div id="list"></div>

    <button onclick="exportPDF()">📄 Export PDF</button>
    <button onclick="shareTrip()">🔗 Share</button>
</div>

<div>
    <h3>🗺 Map</h3>
    <div id="map"></div>
</div>

</div>

<script>
let map;
let markers=[];
let stops=[];

function initMap(){
    if(map) return;
    map=L.map('map').setView([52,5],6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
        attribution:'OSM'
    }).addTo(map);
}

// FREE GEOCODING (Nominatim)
async function geocode(q){
    let res=await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${q}`);
    let data=await res.json();
    return data[0];
}

async function addPlace(){
    let val=document.getElementById("place").value;
    if(!val) return;

    let geo=await geocode(val);
    if(!geo) return alert("Not found");

    initMap();

    let lat=geo.lat;
    let lon=geo.lon;

    let m=L.marker([lat,lon],{draggable:true})
        .addTo(map)
        .bindPopup(val);

    markers.push(m);
    stops.push({name:val,lat,lon});

    render();
}

function render(){
    let box=document.getElementById("list");
    box.innerHTML="";

    stops.forEach((s,i)=>{
        box.innerHTML+=`<div class="stop">${i+1}. ${s.name}</div>`;
    });
}

// SHARE
function shareTrip(){
    let url=window.location.origin+window.location.pathname+"?tool=plan&data="+encodeURIComponent(JSON.stringify(stops));
    navigator.clipboard.writeText(url);
    alert("Copied share link");
}

// PDF EXPORT
function exportPDF(){
    const {jsPDF}=window.jspdf;
    let doc=new jsPDF();

    doc.text("Travel OS Trip",10,10);

    stops.forEach((s,i)=>{
        doc.text(`${i+1}. ${s.name}`,10,20+i*10);
    });

    doc.save("trip.pdf");
}
</script>

<?php endif; ?>

<!-- ================= VISA ================= -->
<?php if ($tool=="visa"): ?>

<h3>🛂 Visa</h3>

<form method="POST">
<input name="from" placeholder="Passport">
<input name="to" placeholder="Destination">
<button>Check</button>
</form>

<?php if ($result): ?>
<div class="card">
<?= $result[0] ?><br>
<?= $result[1] ?> days<br>
<?= $result[2] ?>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- ================= SCHENGEN ================= -->
<?php if ($tool=="schengen"): ?>

<h3>🇪🇺 Schengen</h3>

<form method="POST">
<input name="used" type="number" placeholder="Days used">
<button>Calculate</button>
</form>

<?php if ($result): ?>
<div class="card">
Used: <?= $result[0] ?><br>
Remaining: <?= $result[1] ?><br>
Status: <?= $result[2] ?>
</div>
<?php endif; ?>

<?php endif; ?>

</div>

</body>
</html>
