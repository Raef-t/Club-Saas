"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { ChevronDownIcon } from "@/components/icons/Icons";

export default function Dropdown({
  options = [],
  value,
  onChange,
  placeholder = "اختر",
  icon: Icon,
  className = "",
  buttonClassName = "",
  menuClassName = "",
  disabled = false,
}) {
  const [open, setOpen] = useState(false);
  const containerRef = useRef(null);

  const selectedOption = useMemo(
    () => options.find((option) => option.value === value),
    [options, value],
  );

  useEffect(() => {
    if (!open) return;

    function handlePointerDown(event) {
      if (!containerRef.current?.contains(event.target)) {
        setOpen(false);
      }
    }

    function handleKeyDown(event) {
      if (event.key === "Escape") {
        setOpen(false);
      }
    }

    document.addEventListener("pointerdown", handlePointerDown);
    document.addEventListener("keydown", handleKeyDown);

    return () => {
      document.removeEventListener("pointerdown", handlePointerDown);
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [open]);

  function selectOption(option) {
    onChange?.(option.value);
    setOpen(false);
  }

  return (
    <div ref={containerRef} className={`relative ${className}`} dir="rtl">
      <button
        type="button"
        className={`app-input flex h-10 w-full items-center justify-between gap-3 px-3 text-sm outline-none transition hover:border-app-yellow/50 focus:border-app-yellow/70 disabled:cursor-not-allowed disabled:opacity-60 ${buttonClassName}`}
        aria-haspopup="listbox"
        aria-expanded={open}
        disabled={disabled}
        onClick={() => setOpen((current) => !current)}
      >
        <span className="flex min-w-0 items-center gap-2">
          {Icon && <Icon className="size-4 shrink-0 text-app-muted-light" />}
          <span className="truncate text-app-text">
            {selectedOption?.label || placeholder}
          </span>
        </span>
        <ChevronDownIcon
          className={`size-4 shrink-0 text-app-muted-light transition ${
            open ? "rotate-180 text-app-yellow" : ""
          }`}
        />
      </button>

      {open && (
        <div
          className={`absolute right-0 z-50 mt-2 max-h-72 w-full overflow-hidden rounded-xl border border-app-line bg-app-card shadow-[0_18px_50px_rgba(0,0,0,0.35)] ${menuClassName}`}
          role="listbox"
        >
          <div className="max-h-72 overflow-y-auto p-1 scrollbar-thin">
            {options.map((option) => {
              const selected = option.value === value;

              return (
                <button
                  key={option.value}
                  type="button"
                  className={`flex h-10 w-full items-center justify-between rounded-lg px-3 text-sm transition ${
                    selected
                      ? "bg-app-yellow-soft text-app-yellow"
                      : "text-app-text hover:bg-app-card-hover"
                  }`}
                  role="option"
                  aria-selected={selected}
                  onClick={() => selectOption(option)}
                >
                  <span className="truncate">{option.label}</span>
                  {selected && (
                    <span className="size-2 rounded-full bg-app-yellow" />
                  )}
                </button>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
