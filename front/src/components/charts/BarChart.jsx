export default function BarChart({ data = [] }) {
  const max = Math.max(...data.map((item) => item.value), 1);

  return (
    <div className="flex h-[142px] items-end justify-between gap-1 px-2 pb-3" dir="rtl">
      {data.map((item, index) => (
        <div
          key={`${item.label}-${item.value}-${index}`}
          className="flex h-full flex-1 flex-col items-center justify-end gap-1.5 text-center"
        >
          <div
            className="w-full max-w-[20px] bg-app-yellow rounded-t-[3px]"
            style={{ height: `${Math.max((item.value / max) * 90, 5)}px` }}
          />
          <span className="text-[9px] text-app-muted-light leading-[1.3] w-full break-words">
            {String(item.label).split(" - ").map((line, i) => (
              <span key={i} className="block">{line}</span>
            ))}
          </span>
        </div>
      ))}
    </div>
  );
}
