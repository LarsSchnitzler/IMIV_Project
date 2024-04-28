//---declaring some variables before DOMContentLoaded eventlistener, because fetchData cant read cloud_cover for some reason---
var cloud_cover = {};
var lightning_potential = {};
var precipitation = {};
var surface_pressure = {};
var temperature_2m = {};
var wind_speed_10m = {};

//-------------------Functions-------------------
function configureCanvasContainer(canvasContainer, data, titleString){ //canvasContainer should be id of container, data should be object with date_time as keys, titleString should be string
  const ct = document.getElementById(canvasContainer);
  const title = ct.querySelector('h3');
  const canvas = ct.querySelector('canvas');

  // Set title
  title.textContent = titleString;

  // Extract dates and values from data
  const dates = Object.keys(data);
  const values = Object.values(data);

  // Create chart
  new Chart(canvas, {
    type: 'line',
    data: {
      labels: dates,
      datasets: [{
        label: titleString,
        data: values,
        fill: false,
        borderColor: 'rgb(75, 192, 192)',
        tension: 0.1
      }]
    },
    options: {
      scales: {
        x: {
          type: 'time',
          time: {
            unit: 'hour'
          }
        },
        y: {
          beginAtZero: true
        }
      }
    }
  });
}

//-------------------Async Functions-------------------
async function fetchData() {
  try {
    const response = await fetch('/db_to_frontend.php');
    const data = await response.json();

    if (data['DB_Connection'] === 'successful' && data['DB_WeatherQuery'] === undefined && data['DB_UnitsQuery'] === undefined) {
      //separate data[weather] into objects indexed by date_time
      for (var i = 0; i < data['weather'].length; i++) {
        cloud_cover[data['weather'][i]['date_time']] = data['weather'][i]['cloud_cover'];
        lightning_potential[data['weather'][i]['date_time']] = data['weather'][i]['lightning_potential'];
        precipitation[data['weather'][i]['date_time']] = data['weather'][i]['precipitation'];
        surface_pressure[data['weather'][i]['date_time']] = data['weather'][i]['surface_pressure'];
        temperature_2m[data['weather'][i]['date_time']] = data['weather'][i]['temperature_2m'];
        wind_speed_10m[data['weather'][i]['date_time']] = data['weather'][i]['wind_speed_10m'];
      }
    }
  } catch (error) {
    console.log('Error:', error);
  }
}

async function main() {
  await fetchData();

  //Throw all weatherVariables into one big weatherVariables array to iterate through
  const weatherVariables = [cloud_cover, lightning_potential, precipitation, surface_pressure, temperature_2m, wind_speed_10m];
  //make an array with all variable names to iterate through
  const variableNames = ['Cloud Cover', 'Lightning Potential', 'Precipitation', 'Surface Pressure', 'Temperature at 2m', 'Wind Speed at 10m'];

  for (var i = 0; i < canvasAmount; i++){
    configureCanvasContainer('ct' + (i + 1), weatherVariables[i], variableNames[i] + ' vs. Time');
  }

}

//-------------------Eventlisteners-------------------
document.addEventListener('DOMContentLoaded', (event) => {

  //-------------------Initializing Variables-------------------
  canvases = document.querySelectorAll('canvas');
  canvasAmount = canvases.length;

  main();
});