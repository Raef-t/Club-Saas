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
  searchable = false,
}) {
  const [open, setOpen] = useState(false);
  const [dropUp, setDropUp] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const containerRef = useRef(null);
  const searchInputRef = useRef(null);

  const selectedOption = useMemo(
    () => options.find((option) => option.value === value),
    [options, value],
  );

  function checkPosition() {
    if (!containerRef.current) return;
    const rect = containerRef.current.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    setDropUp(spaceBelow < 300 && spaceAbove > spaceBelow);
  }

  useEffect(() => {
    if (!open) return;

    checkPosition();

    function handleScroll() {
      checkPosition();
    }

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
    window.addEventListener("scroll", handleScroll, true);
    window.addEventListener("resize", handleScroll);

    return () => {
      document.removeEventListener("pointerdown", handlePointerDown);
      document.removeEventListener("keydown", handleKeyDown);
      window.removeEventListener("scroll", handleScroll, true);
      window.removeEventListener("resize", handleScroll);
    };
  }, [open]);

  useEffect(() => {
    if (open && searchable && searchInputRef.current) {
      setTimeout(() => {
        searchInputRef.current?.focus();
      }, 50);
    }
    if (!open) {
      setSearchQuery("");
    }
  }, [open, searchable]);

  function selectOption(option) {
    onChange?.(option.value);
    setOpen(false);
  }

  const filteredOptions = useMemo(() => {
    if (!searchable || !searchQuery.trim()) return options;
    return options.filter(opt => 
      opt.label.toLowerCase().includes(searchQuery.toLowerCase())
    );
  }, [options, searchable, searchQuery]);

  const hasHeight = useMemo(() => {
    return buttonClassName.split(' ').some(c => c.startsWith('h-'));
  }, [buttonClassName]);

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
        } ${!hasHeight ? "h-10" : ""}`}
        aria-haspopup="listbox"
        aria-expanded={open}
        disabled={disabled}
        onClick={() => {
          if (disabled) return;
          if (!open) {
            checkPosition();
            setOpen(true);
          } else {
            setOpen(false);
          }
        }}
      >
        <span className="flex items-center gap-2 px-3 h-full">
          {Icon && <Icon className="size-5 text-app-muted-light" />}
          {selectedOption ? (
            <span className="text-app-text text-sm">{selectedOption.label}</span>
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
          className={`absolute right-0 z-50 ${dropUp ? "bottom-full mb-2" : "top-full mt-2"} max-h-72 w-full flex flex-col overflow-hidden rounded-xl border border-app-line bg-app-card shadow-[var(--app-elevated-shadow)] ${menuClassName}`}
          role="listbox"
        >
          {searchable && (
            <div className="p-2 border-b border-app-line shrink-0">
              <input
                ref={searchInputRef}
                type="text"
                className="w-full bg-app-card-soft border border-app-line rounded-lg h-9 px-3 text-sm text-app-text placeholder-app-muted outline-none focus:border-app-yellow transition"
                placeholder="ابحث..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                onClick={(e) => e.stopPropagation()}
              />
            </div>
          )}
          <div className="max-h-60 overflow-y-auto p-1 scrollbar-thin">
            {filteredOptions.length > 0 ? filteredOptions.map((option) => {
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
                  onClick={(e) => {
                    e.stopPropagation();
                    selectOption(option);
                  }}
                >
                  <span className="truncate">{option.label}</span>
                  {selected && (
                    <span className="size-2 rounded-full bg-app-yellow" />
                  )}
                </button>
              );
            }) : (
              <div className="flex h-10 items-center justify-center text-sm text-app-muted-light">
                لا توجد نتائج
              </div>
            )}
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
