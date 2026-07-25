"use client";

import { useState, useRef } from "react";
import { ArrowUpIcon, TagIcon, TrashIcon } from "@/components/icons/Icons";

export function UploadBox({
  label = "المرفقات",
  subtitle = "إيصالات، فواتير، صور",
  accept = ".pdf,.png,.jpg,.jpeg",
  multiple = true,
  maxSizeMB = 2,
  value = [],
  onChange,
  className = "",
}) {
  const [isDragOver, setIsDragOver] = useState(false);
  const fileInputRef = useRef(null);

  const handleDragOver = (e) => {
    e.preventDefault();
    setIsDragOver(true);
  };

  const handleDragLeave = () => {
    setIsDragOver(false);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    setIsDragOver(false);
    
    if (e.dataTransfer.files) {
      processFiles(e.dataTransfer.files);
    }
  };

  const handleFileSelect = (e) => {
    if (e.target.files) {
      processFiles(e.target.files);
    }
  };

  const processFiles = (fileList) => {
    const validFiles = [];
    const maxSizeBytes = maxSizeMB * 1024 * 1024;

    for (let i = 0; i < fileList.length; i++) {
      const file = fileList[i];
      if (file.size <= maxSizeBytes) {
        validFiles.push(file);
      } else {
        alert(`الملف "${file.name}" يتجاوز الحد الأقصى للحجم (${maxSizeMB} ميجابايت)`);
      }
    }

    if (validFiles.length === 0) return;

    let updatedFiles;
    if (multiple) {
      updatedFiles = [...value, ...validFiles];
    } else {
      updatedFiles = [validFiles[0]];
    }

    onChange?.(updatedFiles);
  };

  const handleRemoveFile = (indexToRemove) => {
    const updatedFiles = value.filter((_, index) => index !== indexToRemove);
    onChange?.(updatedFiles);
  };

  const triggerFileInput = () => {
    fileInputRef.current?.click();
  };

  return (
    <div className={`rounded-2xl bg-app-card-soft p-5 border border-app-line/40 ${className}`} dir="rtl">
      <div className="mb-4 flex items-center justify-between">
        <span className="flex items-center gap-2 text-sm text-white font-medium">
          <TagIcon className="size-4 text-app-yellow" />
          {label}
        </span>
        {subtitle && <span className="text-xs text-app-muted-light">{subtitle}</span>}
      </div>

      <input
        type="file"
        ref={fileInputRef}
        onChange={handleFileSelect}
        accept={accept}
        multiple={multiple}
        className="hidden"
      />

      <div
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={triggerFileInput}
        className={`flex h-[110px] flex-col items-center justify-center rounded-2xl border border-dashed transition-all duration-300 cursor-pointer text-center p-4 select-none ${
          isDragOver
            ? "border-app-yellow bg-app-yellow/5 scale-[0.99]"
            : "border-app-muted/40 hover:border-app-yellow/50 bg-black/20 hover:bg-black/35"
        }`}
      >
        <ArrowUpIcon className={`mb-2 size-5 text-white transition-transform duration-300 ${isDragOver ? "-translate-y-1 scale-110 text-app-yellow" : ""}`} />
        <p className="text-sm font-medium text-white/95">اسحب الملفات هنا أو اضغط للاختيار</p>
        <p className="mt-1 text-[11px] text-app-muted-light">
          {accept.toUpperCase().replace(/\./g, " ").trim()} &nbsp;|&nbsp; حتى {maxSizeMB} MB
        </p>
      </div>

      {value.length > 0 && (
        <div className="mt-4 space-y-2 max-h-36 overflow-y-auto pr-1">
          {value.map((file, index) => {
            const isImage = file.type?.startsWith("image/") || file.name?.match(/\.(jpg|jpeg|png|gif|webp)$/i) || (typeof file === "string" && file.match(/\.(jpg|jpeg|png|gif|webp)/i)) || (file?.url && file.url.match(/\.(jpg|jpeg|png|gif|webp)/i));
            const fileSizeStr = file.size ? (file.size / (1024 * 1024)).toFixed(2) + " MB" : "";
            const fileName = file.name || (file?.url ? file.url.split("/").pop() : typeof file === "string" ? file.split("/").pop() : "file");
            
            // Safe helper for image preview object URL
            let previewUrl = "";
            if (isImage) {
              if (file instanceof File) {
                try {
                  previewUrl = URL.createObjectURL(file);
                } catch (e) {
                  console.error(e);
                }
              } else if (typeof file === "string") {
                previewUrl = file.startsWith("http") || file.startsWith("blob:") ? file : `http://31.70.108.63/${file.replace(/^\//, '')}`;
              } else if (file?.url) {
                previewUrl = file.url.startsWith("http") || file.url.startsWith("blob:") ? file.url : `http://31.70.108.63/${file.url.replace(/^\//, '')}`;
              }
            }

            return (
              <div
                key={index}
                className="flex items-center justify-between rounded-lg bg-black/30 p-2 border border-app-line/20 hover:border-app-line/40 transition-colors"
              >
                <div className="flex items-center gap-3 overflow-hidden">
                  {previewUrl ? (
                    <div className="size-9 rounded bg-cover bg-center border border-app-line/30 shrink-0" style={{ backgroundImage: `url(${previewUrl})` }} />
                  ) : (
                    <div className="size-9 rounded bg-app-card-soft flex items-center justify-center shrink-0 border border-app-line/30">
                      <span className="text-[10px] font-bold text-app-yellow">
                        {fileName?.split(".").pop()?.toUpperCase() || "FILE"}
                      </span>
                    </div>
                  )}
                  <div className="flex flex-col text-right overflow-hidden">
                    <span className="truncate text-xs font-medium text-white/90" title={fileName}>
                      {fileName}
                    </span>
                    {fileSizeStr && <span className="text-[10px] text-app-muted-light">{fileSizeStr}</span>}
                  </div>
                </div>
                
                <button
                  type="button"
                  onClick={(e) => {
                    e.stopPropagation();
                    handleRemoveFile(index);
                  }}
                  className="p-1.5 text-app-muted hover:text-app-red rounded-lg hover:bg-app-red/10 transition-colors duration-200"
                >
                  <TrashIcon className="size-4" />
                </button>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
