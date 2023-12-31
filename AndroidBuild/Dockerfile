FROM ubuntu:18.04 AS alphatilesbuild
 
# Install Android SDK 
RUN apt-get update && apt-get install -y openjdk-8-jdk-headless && apt-get install -y wget && apt-get install -y unzip 
#RUN apt-get install -y sdkmanager gradle
RUN wget https://dl.google.com/android/repository/sdk-tools-linux-3859397.zip 
RUN unzip sdk-tools-linux-3859397.zip  
RUN mv tools /opt/android-sdk
ENV ANDROID_HOME=/opt/android-sdk/bin
ENV PATH=$ANDROID_HOME/tools:$ANDROID_HOME/tools/bin:$ANDROID_HOME/platform-tools:$PATH 
RUN export JAVA_OPTS='-XX:+IgnoreUnrecognizedVMOptions --add-modules java.xml.bind'
RUN yes | /opt/android-sdk/bin/sdkmanager --licenses
RUN echo y | /opt/android-sdk/bin/sdkmanager "platforms;android-34" && echo y | /opt/android-sdk/bin/sdkmanager "build-tools;33.0.1"
RUN mv /opt/licenses /opt/android-sdk/bin

# Build the app 
RUN apt-get install -y openjdk-17-jdk-headless
COPY . /
RUN cd /app && ../gradlew assembleDebug

WORKDIR /app
#RUN mv /app/app/build/outputs/apk/German/debug/alphaTiles.Blue.German.V1.5.3.apk /app/test.apk

# Run the app 
RUN chmod +x /app/build/outputs/apk/German/debug/alphaTiles.Blue.German.V1.5.3.apk
CMD ["/app/build/outputs/apk/German/debug/alphaTiles.Blue.German.V1.5.3.apk"]

#docker run -d --name alphatiles_container alphatiles
#docker cp alphatiles_container:/app/build/outputs/apk/German/debug/alphaTiles.Blue.German.V1.5.3.apk /Users/philipperry/Downloads/test
#COPY /app/app/build/outputs/apk/German/release/alphaTiles.Blue.German.V1.5.3.apk .
#CMD ["/bin/bash"]