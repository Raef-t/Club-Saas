import Image from "next/image";

/**
 * Renders the product logo inside a consistently sized responsive frame.
 */
export default function BrandLogo({
  src = "/img/logo.jpeg",
  className = "",
  imageClassName = "",
  priority = false,
}) {
  return (
    <div className={`grid place-items-center overflow-hidden rounded-2xl ${className}`}>
      <Image
        src={src}
        alt="TechnoGYM"
        width={500}
        height={500}
        className={`h-full w-full object-contain ${imageClassName}`}
        priority={priority}
      />
    </div>
  );
}
