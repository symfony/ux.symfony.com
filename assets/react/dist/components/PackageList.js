import React from 'react';
export default function (props) {
  if (props.packages.length === 0) {
    return 'No packages found';
  }
  return /*#__PURE__*/React.createElement("div", {
    className: "pt-3 grid gap-2 grid-cols-[repeat(auto-fill,minmax(260px,1fr))]"
  }, props.packages.map(item => /*#__PURE__*/React.createElement("div", {
    className: "group relative flex gap-6 p-2 self-stretch border border-surface rounded-md bg-body",
    key: item.id,
    style: {
      '--color': item.color,
      '--gradient': item.gradient || 'none',
      '--logo-size': '48px'
    }
  }, /*#__PURE__*/React.createElement("div", {
    className: "flex items-center justify-center shrink-0 rounded-[14%] [background-image:var(--gradient)] bg-(--color) w-(--logo-size) h-(--logo-size) [&_img]:w-[calc(0.4*var(--logo-size))] [&_img]:h-auto [&_img]:filter-[drop-shadow(4px_4px_4px_rgba(0,0,0,0))]"
  }, /*#__PURE__*/React.createElement("img", {
    src: item.imageUrl,
    alt: `Image for the ${item.humanName} UX package`,
    style: item.imageScale ? {
      width: `calc(${item.imageScale} * var(--logo-size))`
    } : undefined
  })), /*#__PURE__*/React.createElement("h3", {
    className: "font-title font-semibold text-xl leading-normal text-body-text m-0"
  }, /*#__PURE__*/React.createElement("a", {
    href: item.url
  }, item.humanName)))));
}