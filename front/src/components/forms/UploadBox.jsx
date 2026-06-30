import { ArrowUpIcon, TagIcon } from "@/components/icons/Icons";

export function UploadBox({ compact = false, className = "" }) {
  return (
    <div className={`rounded-2xl bg-app-card-soft p-5 ${className}`}>
      <div className="mb-4 flex items-center justify-between">
        <span className="flex items-center gap-2 text-base text-white">
          <TagIcon className="size-4 text-app-yellow" />
          المرفقات
        </span>
        <span className="text-sm text-app-muted-light">إيصالات، فواتير، صور</span>
      </div>
      <div className={`flex ${compact ? "h-[84px]" : "h-[118px]"} flex-col items-center justify-center rounded-2xl border border-dashed border-app-muted/50 text-center`}>
        <ArrowUpIcon className="mb-2 size-6 text-white" />
        <p className="text-base text-white">اسحب الملفات هنا أو اضغط للاختيار</p>
        <p className="mt-1 text-sm text-app-muted-light">PDF , PNG , JPG&nbsp;&nbsp; حتى 10 MB</p>
      </div>
    </div>
  );
}
