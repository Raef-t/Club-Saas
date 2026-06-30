function buildPointList(points, width, chartTop, axisY, max) {
  if (!points.length) return [];
  const step = width / (points.length - 1 || 1);
  const chartHeight = axisY - chartTop;

  return points.map((point, index) => ({
    x: index * step,
    y: axisY - (point / max) * chartHeight,
  }));
}

function buildPath(points) {
  if (!points.length) return "";

  return points
    .map(({ x, y }, index) => `${index === 0 ? "M" : "L"}${x},${y}`)
    .join(" ");
}

function buildFill(path, points, axisY) {
  if (!path || !points.length) return "";

  const first = points[0];
  const last = points[points.length - 1];
  return `${path} L${last.x},${axisY} L${first.x},${axisY} Z`;
}

export default function LineChart({ data, legend }) {
  const width = 600;
  const height = 166;
  const chartTop = 10;
  const axisY = 138;
  const labels = data.labels || [];
  const yellow = data.yellow || [];
  const green = data.green || [];
  const max = Math.max(...yellow, ...green, 1);
  const yellowPoints = buildPointList(yellow, width, chartTop, axisY, max);
  const greenPoints = buildPointList(green, width, chartTop, axisY, max);
  const yellowPath = buildPath(yellowPoints);
  const greenPath = buildPath(greenPoints);
  const yellowFill = buildFill(yellowPath, yellowPoints, axisY);
  const greenFill = buildFill(greenPath, greenPoints, axisY);
  const labelStep = width / (labels.length - 1 || 1);

  return (
    <div className="px-6 pb-3">
      <div className="relative h-[132px]" dir="ltr">
        <svg
          viewBox={`0 0 ${width} ${height}`}
          preserveAspectRatio="none"
          className="absolute inset-x-0 top-0 h-full w-full overflow-visible"
        >
          <defs>
            <linearGradient id="chartGreenFill" x1="0" x2="0" y1="0" y2="1">
              <stop offset="0%" stopColor="#13AC49" stopOpacity="0.35" />
              <stop offset="100%" stopColor="#13AC49" stopOpacity="0" />
            </linearGradient>
            <linearGradient id="chartYellowFill" x1="0" x2="0" y1="0" y2="1">
              <stop offset="0%" stopColor="#FCCD03" stopOpacity="0.22" />
              <stop offset="100%" stopColor="#FCCD03" stopOpacity="0" />
            </linearGradient>
          </defs>

          <line
            x1="0"
            x2={width}
            y1={axisY}
            y2={axisY}
            stroke="#EFEFEF"
            strokeWidth="1.4"
          />

          {labels.map((label, index) => {
            const x = index * labelStep;

            return (
              <g key={`${label}-${index}`}>
                <line
                  x1={x}
                  x2={x}
                  y1={axisY}
                  y2={axisY + 5}
                  stroke="#EFEFEF"
                  strokeWidth="1"
                />
                <text
                  x={x}
                  y="161"
                  fill="#FFFFFF"
                  fontSize="20"
                  textAnchor="middle"
                  dominantBaseline="middle"
                >
                  {label}
                </text>
              </g>
            );
          })}

          {greenFill && <path d={greenFill} fill="url(#chartGreenFill)" />}
          {yellowFill && <path d={yellowFill} fill="url(#chartYellowFill)" />}
          {greenPath && (
            <path
              d={greenPath}
              fill="none"
              stroke="#13AC49"
              strokeWidth="2.5"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          )}
          {yellowPath && (
            <path
              d={yellowPath}
              fill="none"
              stroke="#FCCD03"
              strokeWidth="2.5"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          )}
          {yellowPoints.map((point, index) => (
            <circle key={index} cx={point.x} cy={point.y} r="4.5" fill="#FCCD03" />
          ))}
        </svg>
      </div>

      {legend && (
        <div className="mt-2 flex items-center justify-center gap-16 text-sm text-app-text">
          <span className="inline-flex items-center gap-2">
            <i className="size-3 rounded-full bg-app-yellow" />
            {legend.yellow}
          </span>
          <span className="inline-flex items-center gap-2">
            <i className="size-3 rounded-full bg-[#13AC49]" />
            {legend.green}
          </span>
        </div>
      )}
    </div>
  );
}
