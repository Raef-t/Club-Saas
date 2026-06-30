export default function BarChart({ data = [] }) {
  const max = Math.max(...data.map((item) => item.value), 1);

  return (
    <div className="flex h-[142px] items-end justify-center gap-[5px] px-5 pb-3" dir="ltr">
      {data.map((item) => (
        <div
          key={`${item.label}-${item.value}`}
          className="flex h-full w-[21px] shrink-0 flex-col items-center justify-end gap-2"
        >
          <div
            className="w-[20.5px] bg-[#d2c06d]"
            style={{ height: `${Math.max((item.value / max) * 111, 5)}px` }}
          />
          <span className="text-[10px] text-white">{item.label}</span>
        </div>
      ))}
    </div>
  );
}
