import { Application } from "@hotwired/stimulus";
import "@hotwired/turbo";
import LiveController from "@symfony/ux-live-component";
import DemoCartController from "../controllers/demo-cart-controller.js";
import DemoCartRowController from "../controllers/demo-cart-row-controller.js";
import InspectorDemoController from "../controllers/inspector-demo-controller.js";
import DemoProductController from "../controllers/demo-product-controller.js";
import DemoResultsController from "../controllers/demo-results-controller.js";

const app = Application.start();

app.register("live", LiveController);
app.register("demo-cart", DemoCartController);
app.register("demo-cart-row", DemoCartRowController);
app.register("inspector-demo", InspectorDemoController);
app.register("demo-product", DemoProductController);
app.register("demo-results", DemoResultsController);
