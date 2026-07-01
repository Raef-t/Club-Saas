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
  error,
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
        className={`flex w-full items-center justify-between rounded-lg transition outline-none ${
          error
            ? "border border-app-red bg-app-red/5 focus:border-app-red focus:ring-1 focus:ring-app-red"
            : buttonClassName
        } ${
          disabled
            ? "cursor-not-allowed opacity-60"
            : open
            ? "border-app-yellow ring-1 ring-app-yellow"
            : !buttonClassName.includes("border-") 
            ? "border border-app-muted/50 bg-app-panel-soft/40 hover:border-app-yellow/50 focus:border-app-yellow" 
            : ""
        }`}
        aria-haspopup="listbox"
        aria-expanded={open}
        disabled={disabled}
        onClick={() => !disabled && setOpen(!open)}
      >
        <span className="flex items-center gap-2 px-3 h-10">
          {Icon && <Icon className="size-5 text-app-muted-light" />}
          {selectedOption ? (
            <span className="text-white text-sm">{selectedOption.label}</span>
          ) : (
            <span className="text-app-muted-light text-sm">{placeholder}</span>
          )}
        </span>
        <ChevronDownIcon
          className={`size-4 ml-3 shrink-0 text-app-muted-light transition ${
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
      {error && (
        <span className="mt-1.5 block text-xs text-app-red text-right w-full">
          {error}
        </span>
      )}
    </div>
  );
}
