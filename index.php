<?php
// Sesuaikan dengan setting MySQL
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "latihan";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM titik_perumahan";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Titik Perumahan</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; }
        #map { height: 450px; width: 100%; }
        #isi { padding: 12px 16px; }
    </style>
</head>
<body>

<!-- BAGIAN ATAS: PETA -->
<div id="map"></div>

<!-- BAGIAN BAWAH: TABEL -->
<div id="isi">
<?php
echo "<a href='input/index.html'>Input</a><br><br>";

if ($result->num_rows > 0) {
    echo "<table border='1px'><tr>
            <th>OGC FID</th>
            <th>Nama Objek</th>
            <th>Koordinat X</th>
            <th>Koordinat Y</th>
            <th>Kecamatan</th>
            <th colspan='3'>Aksi</th>
          </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr><td align='right'>" . $row["ogc_fid"] . "</td>" .
                 "<td>" . htmlspecialchars($row["namobj"]) . "</td>" .
                 "<td>" . $row["koor_x"] . "</td>" .
                 "<td>" . $row["koor_y"] . "</td>" .
                 "<td>" . htmlspecialchars($row["wadmkc"]) . "</td>" .
                 "<td><a href='#' onclick='fokusKe(" . (int)$row["ogc_fid"] . "); return false;'>peta</a></td>" .
                 "<td><a href='edit/index.php?id=" . $row["ogc_fid"] . "'>edit</a></td>" .
                 "<td><a href='delete.php?id=" . $row["ogc_fid"] . "' onclick=\"return confirm('Hapus data ini?')\">hapus</a></td>" .
              "</tr>";
    }
    echo "</table>";
} else {
    echo "0 results";
}

$conn->close();
?>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    /* Peta */
    var map = L.map('map').setView([-7.62, 110.80], 11);

    var basemap1 = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri'
    });
    var basemap2 = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    });
    basemap1.addTo(map);

    /* Simpan layer tiap titik berdasarkan id, supaya bisa dicari dari tabel */
    var layerById = {};

    var perumahan = L.geoJSON(null, {
        pointToLayer: function (feature, latlng) {
            return L.circleMarker(latlng, {
                radius: 8,
                fillColor: "#C0392B",
                color: "#fff",
                weight: 1.5,
                opacity: 1,
                fillOpacity: 0.9
            });
        },
        onEachFeature: function (feature, layer) {
            layerById[feature.properties.id] = layer;
            layer.bindPopup(
                "<b>" + (feature.properties.namobj || "(tanpa nama)") + "</b><br>" +
                "Kecamatan: " + (feature.properties.wadmkc || "-") + "<br>" +
                "<a href='edit/index.php?id=" + feature.properties.id + "'>Edit data</a>"
            );
        }
    }).addTo(map);

    L.control.layers(
        { "Esri Imagery": basemap1, "OpenStreetMap": basemap2 },
        { "Titik Perumahan": perumahan },
        { collapsed: false }
    ).addTo(map);

    L.control.scale({ maxWidth: 150, position: 'bottomright' }).addTo(map);

    /* Dipanggil saat klik "peta" di tabel */
    function fokusKe(id) {
        var layer = layerById[id];
        if (!layer) {
            alert("Titik ini tidak punya koordinat yang valid.");
            return;
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
        map.setView(layer.getLatLng(), 17);
        layer.openPopup();
    }

    /* Ambil data titik dari database (data.php) */
    fetch('data.php')
        .then(function (response) { return response.json(); })
        .then(function (geojson) {
            perumahan.addData(geojson);
            var bounds = perumahan.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds);
            }
        })
        .catch(function (err) {
            console.error("Gagal memuat data:", err);
        });
</script>

</body>
</html>