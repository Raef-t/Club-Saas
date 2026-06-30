function SkeletonBlock({ className = "" }) {
  return (
    <div
      className={`animate-pulse rounded-lg bg-app-card-soft/80 ${className}`}
      aria-hidden="true"
    />
  );
}

function HeaderSkeleton({ actions = 1 }) {
  return (
    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div className="space-y-3 text-end">
        <SkeletonBlock className="mr-auto h-3 w-24" />
        <SkeletonBlock className="mr-auto h-8 w-56" />
        <SkeletonBlock className="mr-auto h-4 w-80 max-w-full" />
      </div>
      {actions > 0 && (
        <div className="flex gap-2">
          {Array.from({ length: actions }).map((_, index) => (
            <SkeletonBlock key={index} className="h-10 w-28" />
          ))}
        </div>
      )}
    </div>
  );
}

function StatsSkeleton({ count = 4 }) {
  return (
    <div className="grid grid-auto-fit gap-5">
      {Array.from({ length: count }).map((_, index) => (
        <div key={index} className="card-shell rounded-2xl p-4">
          <div className="flex items-center justify-between gap-3">
            <SkeletonBlock className="size-11 rounded-full" />
            <div className="flex-1 space-y-2">
              <SkeletonBlock className="mr-auto h-4 w-24" />
              <SkeletonBlock className="mr-auto h-3 w-32" />
            </div>
          </div>
          <SkeletonBlock className="mx-auto mt-5 h-7 w-24" />
        </div>
      ))}
    </div>
  );
}

function ListSkeleton({ count = 5, itemClassName = "h-16" }) {
  return (
    <div className="space-y-3 pt-3">
      {Array.from({ length: count }).map((_, index) => (
        <SkeletonBlock key={index} className={itemClassName} />
      ))}
    </div>
  );
}

function TableSkeleton({ rows = 5, columns = 6 }) {
  return (
    <div className="space-y-3 pt-3">
      {Array.from({ length: rows }).map((_, rowIndex) => (
        <div
          key={rowIndex}
          className="grid gap-3 rounded-lg bg-app-card-soft/60 px-3 py-4"
          style={{
            gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))`,
          }}
        >
          {Array.from({ length: columns }).map((_, columnIndex) => (
            <SkeletonBlock key={columnIndex} className="h-4" />
          ))}
        </div>
      ))}
    </div>
  );
}

function DetailsSkeleton({ sections = 3, itemsPerSection = 4 }) {
  return (
    <div className="space-y-6">
      {Array.from({ length: sections }).map((_, sectionIndex) => (
        <section key={sectionIndex} className="space-y-3">
          <SkeletonBlock className="mr-auto h-4 w-28" />
          <div className="grid gap-3 sm:grid-cols-2">
            {Array.from({ length: itemsPerSection }).map((_, itemIndex) => (
              <SkeletonBlock key={itemIndex} className="h-16" />
            ))}
          </div>
        </section>
      ))}
    </div>
  );
}

function CardGridSkeleton({ count = 4, minHeight = "min-h-32" }) {
  return (
    <div className="grid grid-auto-fit gap-5">
      {Array.from({ length: count }).map((_, index) => (
        <SkeletonBlock key={index} className={`${minHeight} rounded-2xl`} />
      ))}
    </div>
  );
}

function renderBlock(block, index) {
  switch (block.type) {
    case "header":
      return <HeaderSkeleton key={index} {...block} />;
    case "stats":
      return <StatsSkeleton key={index} {...block} />;
    case "table":
      return <TableSkeleton key={index} {...block} />;
    case "list":
      return <ListSkeleton key={index} {...block} />;
    case "details":
      return <DetailsSkeleton key={index} {...block} />;
    case "cards":
      return <CardGridSkeleton key={index} {...block} />;
    case "custom":
      return <SkeletonBlock key={index} className={block.className} />;
    case "group":
      return (
        <div key={index} className={block.className || "space-y-4"}>
          {(block.blocks || []).map(renderBlock)}
        </div>
      );
    default:
      return <SkeletonBlock key={index} className={block.className || "h-10"} />;
  }
}

export { SkeletonBlock as Skeleton };

export default function SkeletonPage({ blocks = [], className = "space-y-6" }) {
  return (
    <div className={className} role="status" aria-label="جاري التحميل">
      {blocks.map(renderBlock)}
    </div>
  );
}
