const MENU_GAP = 8;
const VIEWPORT_MARGIN = 8;
const MENU_MAX_HEIGHT = 288;
const MENU_MIN_HEIGHT = 48;
const OPTION_HEIGHT = 40;
const MENU_PADDING = 8;
const SEARCH_SECTION_HEIGHT = 54;
const MAX_VISIBLE_OPTIONS = 6;

/**
 * Keeps a number inside the supplied inclusive range.
 */
function clamp(value, minimum, maximum) {
  return Math.min(Math.max(value, minimum), maximum);
}

/**
 * Estimates the rendered dropdown height from its visible controls.
 */
function getEstimatedMenuHeight(optionCount, searchable) {
  const visibleOptions = clamp(Number(optionCount) || 1, 1, MAX_VISIBLE_OPTIONS);
  const searchHeight = searchable ? SEARCH_SECTION_HEIGHT : 0;

  return Math.min(MENU_MAX_HEIGHT, visibleOptions * OPTION_HEIGHT + MENU_PADDING + searchHeight);
}

/**
 * Calculates a fixed menu position that stays visible and avoids clipped ancestors.
 */
export function getDropdownMenuPosition(
  rect,
  { optionCount = 0, searchable = false, viewportHeight, viewportWidth },
) {
  const desiredHeight = getEstimatedMenuHeight(optionCount, searchable);
  const spaceBelow = Math.max(0, viewportHeight - rect.bottom - VIEWPORT_MARGIN - MENU_GAP);
  const spaceAbove = Math.max(0, rect.top - VIEWPORT_MARGIN - MENU_GAP);
  const opensUpward = spaceBelow < desiredHeight && spaceAbove > spaceBelow;
  const availableHeight = opensUpward ? spaceAbove : spaceBelow;
  const maxHeight = Math.min(MENU_MAX_HEIGHT, Math.max(MENU_MIN_HEIGHT, availableHeight));
  const renderedHeight = Math.min(desiredHeight, maxHeight);
  const maximumWidth = Math.max(0, viewportWidth - VIEWPORT_MARGIN * 2);
  const width = Math.min(rect.width, maximumWidth);
  const maximumLeft = Math.max(VIEWPORT_MARGIN, viewportWidth - VIEWPORT_MARGIN - width);
  const left = clamp(rect.left, VIEWPORT_MARGIN, maximumLeft);
  const preferredTop = opensUpward ? rect.top - MENU_GAP - renderedHeight : rect.bottom + MENU_GAP;
  const maximumTop = Math.max(VIEWPORT_MARGIN, viewportHeight - VIEWPORT_MARGIN - renderedHeight);

  return {
    left,
    maxHeight,
    opensUpward,
    top: clamp(preferredTop, VIEWPORT_MARGIN, maximumTop),
    width,
  };
}
