"use client";

import { useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";

/**
 * Reusable floating tooltip component tailored to the TechnoGYM theme.
 * Uses createPortal and fixed positioning to prevent clipping in scrollable tables.
 */
export default function AppTooltip({
  children,
  content,
  disabled = false,
  className = "",
  containerClassName = "inline-flex",
  ariaLabel,
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [coords, setCoords] = useState({ top: 0, left: 0, placement: "bottom" });
  const [mounted, setMounted] = useState(false);
  const triggerRef = useRef(null);
  const tooltipRef = useRef(null);

  useEffect(() => {
    setMounted(true);
  }, []);

  const updatePosition = () => {
    if (!triggerRef.current) return;
    const rect = triggerRef.current.getBoundingClientRect();
    const tooltipHeight = tooltipRef.current?.offsetHeight || 80;
    const tooltipWidth = tooltipRef.current?.offsetWidth || 190;

    // Determine vertical placement (flip to top if close to viewport bottom)
    const spaceBelow = window.innerHeight - rect.bottom;
    const placeTop = spaceBelow < tooltipHeight + 16 && rect.top > tooltipHeight + 16;

    const top = placeTop ? rect.top - 8 : rect.bottom + 8;

    // Center horizontally and clamp within viewport margins
    let left = rect.left + rect.width / 2;
    const halfWidth = tooltipWidth / 2;
    if (left - halfWidth < 12) {
      left = halfWidth + 12;
    } else if (left + halfWidth > window.innerWidth - 12) {
      left = window.innerWidth - halfWidth - 12;
    }

    setCoords({
      top,
      left,
      placement: placeTop ? "top" : "bottom",
    });
  };

  const handleMouseEnter = () => {
    if (disabled || !content) return;
    updatePosition();
    setIsOpen(true);
  };

  const handleMouseLeave = () => {
    setIsOpen(false);
  };

  useEffect(() => {
    if (isOpen) {
      updatePosition();
      const handleScrollOrResize = () => updatePosition();
      window.addEventListener("scroll", handleScrollOrResize, true);
      window.addEventListener("resize", handleScrollOrResize);
      return () => {
        window.removeEventListener("scroll", handleScrollOrResize, true);
        window.removeEventListener("resize", handleScrollOrResize);
      };
    }
  }, [isOpen]);

  return (
    <div
      ref={triggerRef}
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
      onFocus={handleMouseEnter}
      onBlur={handleMouseLeave}
      className={`relative ${containerClassName}`}
      aria-label={ariaLabel}
      data-testid="app-tooltip-trigger"
    >
      {children}
      {mounted &&
        isOpen &&
        content &&
        createPortal(
          <div
            ref={tooltipRef}
            role="tooltip"
            dir="rtl"
            style={{
              position: "fixed",
              top: coords.top,
              left: coords.left,
              transform: coords.placement === "top" ? "translate(-50%, -100%)" : "translate(-50%, 0)",
              zIndex: 99999,
            }}
            className={`pointer-events-none z-[99999] min-w-max rounded-xl border border-app-line/80 bg-app-card/95 p-3 shadow-2xl backdrop-blur-md transition-all duration-150 animate-in fade-in-0 zoom-in-95 ${className}`}
          >
            {content}
          </div>,
          document.body,
        )}
    </div>
  );
}
