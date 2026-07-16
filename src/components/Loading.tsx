import Gear from "./svg/Gear";

export default function Loading(): React.JSX.Element {
  return (
    <div id="app-outer-container">
      <div id="app-inner-container">
        <div className="waiting-wrapper">
          <Gear />
        </div>
      </div>
    </div>
  );
}
