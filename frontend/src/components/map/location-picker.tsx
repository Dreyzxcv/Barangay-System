"use client";

import dynamic from "next/dynamic";
import { useEffect } from "react";

const MapContainer = dynamic(() => import("react-leaflet").then((m) => m.MapContainer), { ssr: false });
const TileLayer = dynamic(() => import("react-leaflet").then((m) => m.TileLayer), { ssr: false });
const Marker = dynamic(() => import("react-leaflet").then((m) => m.Marker), { ssr: false });

interface LocationPickerProps {
  latitude?: number;
  longitude?: number;
  onChange: (lat: number, lng: number) => void;
}

export function LocationPicker({ latitude, longitude, onChange }: LocationPickerProps) {
  useEffect(() => {
    import("leaflet/dist/leaflet.css");
  }, []);

  const center: [number, number] = [latitude || 14.5995, longitude || 120.9842];

  return (
    <div className="h-64 overflow-hidden rounded-lg border border-slate-200">
      <MapContainer
        center={center}
        zoom={15}
        style={{ height: "100%", width: "100%" }}
        scrollWheelZoom={false}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        <ClickHandler onChange={onChange} />
        {latitude && longitude ? <Marker position={[latitude, longitude]} /> : null}
      </MapContainer>
    </div>
  );
}

function ClickHandler({ onChange }: { onChange: (lat: number, lng: number) => void }) {
  const { useMapEvents } = require("react-leaflet");
  useMapEvents({
    click(e: { latlng: { lat: number; lng: number } }) {
      onChange(e.latlng.lat, e.latlng.lng);
    },
  });
  return null;
}
