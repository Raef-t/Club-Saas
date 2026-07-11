"use client";

import { createContext, useContext, useState, useEffect } from "react";

const TimeFormatContext = createContext({
  timeFormat: "12",
  setTimeFormat: () => {},
  formatTime: (timeStr) => timeStr,
});

export function TimeFormatProvider({ children }) {
  const [timeFormat, setTimeFormatState] = useState("12");

  useEffect(() => {
    const saved = localStorage.getItem("settings_time_format");
    if (saved === "12" || saved === "24") {
      setTimeFormatState(saved);
    }
  }, []);

  const setTimeFormat = (format) => {
    if (format === "12" || format === "24") {
      setTimeFormatState(format);
      localStorage.setItem("settings_time_format", format);
    }
  };

  const formatTime = (timeStr) => {
    if (!timeStr) return "";
    // Clean up any extra parts (like seconds "HH:MM:SS" -> "HH:MM")
    const cleanTime = timeStr.slice(0, 5);
    const parts = cleanTime.split(":");
    if (parts.length !== 2) return timeStr;

    const hh = parseInt(parts[0], 10);
    const mm = parts[1];

    if (isNaN(hh)) return timeStr;

    if (timeFormat === "24") {
      return cleanTime;
    }

    const isPm = hh >= 12;
    const hour12 = hh % 12 === 0 ? 12 : hh % 12;
    const period = isPm ? "م" : "ص";

    return `${String(hour12).padStart(2, "0")}:${mm} ${period}`;
  };

  return (
    <TimeFormatContext.Provider value={{ timeFormat, setTimeFormat, formatTime }}>
      {children}
    </TimeFormatContext.Provider>
  );
}

export function useTimeFormat() {
  return useContext(TimeFormatContext);
}
